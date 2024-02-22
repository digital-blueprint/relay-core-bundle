<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProviderInterface;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProviderTrait;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\LocalData\LocalDataAccessChecker;
use Dbp\Relay\CoreBundle\LocalData\LocalDataAwareInterface;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConditionNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\ConstantNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Nodes\LogicalNode;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilterProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template T of object
 *
 * @implements StateProviderInterface<T>
 */
abstract class AbstractDataProvider extends AbstractAuthorizationService implements StateProviderInterface
{
    use DataOperationTrait;
    use StateProviderTrait;

    protected const GET_COLLECTION_OPERATION = 1;
    protected const GET_ITEM_OPERATION = 2;

    private const FILTERS_CONTEXT_KEY = 'filters';
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';
    private const LOCAL_DATA_BASE_PATH = 'localData.';

    /** @var Locale */
    private $locale;

    /** @var PreparedFilterProvider */
    private $preparedFilterController;

    /** @var LocalDataAccessChecker */
    private $localDataAccessChecker;

    /** @var PropertyNameCollectionFactoryInterface */
    private $propertyNameCollectionFactory;

    /** @var bool[] */
    private $localDataReadAccessGrantedMap = [];

    /**
     * @deprecated Use self::appendConfigNodeDefinitions instead
     */
    public static function getLocalDataConfigNodeDefinition(): NodeDefinition
    {
        return LocalDataAccessChecker::getConfigNodeDefinition();
    }

    public static function appendConfigNodeDefinitions(ArrayNodeDefinition $rootNode)
    {
        $rootNode->append(LocalDataAccessChecker::getConfigNodeDefinition());
        $rootNode->append(PreparedFilterProvider::getConfigNodeDefinition());
    }

    public function __construct()
    {
        parent::__construct();

        $this->preparedFilterController = new PreparedFilterProvider();
        $this->localDataAccessChecker = new LocalDataAccessChecker();
    }

    /**
     * @required
     */
    public function __injectLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @required
     */
    public function __injectPropertyNameCollectionFactory(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory): void
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
    }

    public function setConfig(array $config)
    {
        parent::setConfig($config);

        $this->localDataAccessChecker->loadConfig($config);
        $this->preparedFilterController->loadConfig($config);

        parent::configure(array_merge(
            $this->localDataAccessChecker->getPolicies(),
            $this->preparedFilterController->getPolicies()));
    }

    /**
     * @throws ApiError
     * @throws \Exception
     */
    protected function getItemInternal(string $id, array $context): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters);

        $item = $this->getItemById($id, $filters, $options);
        $this->removeForbiddenLocalDataAttributeValues([$item], Options::getLocalDataAttributes($options));

        $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::GET_ITEM_OPERATION, $item, $filters);

        return $item;
    }

    /**
     * @throws ApiError
     * @throws \Exception
     */
    protected function getCollectionInternal(array $context): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToGetCollectionUnlessAuthorized($filters);

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null;
        $deserializationGroups = $context[self::GROUPS_CONTEXT_KEY] ?? null;

        $options = $this->createOptions($filters);

        $filter = null;
        if ($filterParameter = Parameters::getFilter($filters)) {
            $filter = $this->createFilter($filterParameter, $resourceClass, $deserializationGroups);
        }

        if ($preparedFilterParameter = Parameters::getPreparedFilter($filters)) {
            $preparedFilter = $this->createPreparedFilter($preparedFilterParameter, $resourceClass, $deserializationGroups);
            $filter = $filter !== null ? $filter->combineWith($preparedFilter) : $preparedFilter;
        }

        $returnEmptyPage = false;
        if ($filter !== null) {
            $filter->simplify();
            if ($filter->isAlwaysFalse()) {
                $returnEmptyPage = true;
            } elseif (!$filter->isAlwaysTrue()) {
                Options::addFilter($options, $filter);
            }
        }

        $pageItems = [];
        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        if (!$returnEmptyPage) {
            $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
            $this->removeForbiddenLocalDataAttributeValues($pageItems, Options::getLocalDataAttributes($options));
        }

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): ?object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    protected function forbidCurrentUserToGetCollectionUnlessAuthorized(array $filters): void
    {
        if (!$this->isCurrentUserAuthorizedToGetCollection($filters)) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'forbidden');
        }
    }

    protected function isCurrentUserAuthorizedToGetCollection(array $filters): bool
    {
        return true;
    }

    /**
     * @throws ApiError
     * @throws \Exception
     */
    private function createOptions(array $filters): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        if ($includeLocalParameter = Parameters::getIncludeLocal($filters)) {
            $referencedLocalDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($includeLocalParameter);
            $this->localDataAccessChecker->assertLocalDataAttributesAreDefined($referencedLocalDataAttributes);
            Options::setLocalDataAttributes($options, $referencedLocalDataAttributes);
        }

        return $options;
    }

    /**
     * @throws ApiError
     * @throws \Exception
     */
    private function createFilter($filterParameter, ?string $resourceClass, ?array $deserializationGroups, bool $removeForbiddenLocalDataAttributeConditions = true): Filter
    {
        if ($resourceClass === null || $deserializationGroups === null) {
            throw new \Exception('Provider context must contain \''.self::RESOURCE_CLASS_CONTEXT_KEY.'\' and \''.self::GROUPS_CONTEXT_KEY.'\' when using filters to determine available resource properties.');
        }

        if (is_array($filterParameter) === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, '\''.Parameters::FILTER.'\' parameter must be an array. Square brackets missing.', ErrorIds::FILTER_PARAMETER_MUST_BE_AN_ARRAY);
        }
        try {
            $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
                $filterParameter, $this->getAvailableAttributePaths($resourceClass, $deserializationGroups));
            if ($removeForbiddenLocalDataAttributeConditions) {
                $this->removeForbiddenLocalDataAttributeConditionsFromFilterRecursively($filter->getRootNode());
            }

            return $filter;
        } catch (FilterException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::FILTER_INVALID, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    private function removeForbiddenLocalDataAttributeValues(array $items, array $requestedLocalDataAttributeNames)
    {
        foreach ($requestedLocalDataAttributeNames as $localDataAttributeName) {
            if (!$this->isGrantedReadAccessToLocalDataAttribute($localDataAttributeName)) {
                foreach ($items as $item) {
                    if ($item instanceof LocalDataAwareInterface) {
                        $item->setLocalDataValue($localDataAttributeName, null);
                    }
                }
            }
        }
    }

    private function removeForbiddenLocalDataAttributeConditionsFromFilterRecursively(LogicalNode $logicalNode)
    {
        foreach ($logicalNode->getChildren() as $child) {
            $localDataAttributeName = '';
            if ($child instanceof ConditionNode
                && self::isLocalDataAttributePath($child->getField(), $localDataAttributeName)
                && !$this->isGrantedReadAccessToLocalDataAttribute($localDataAttributeName)) {
                $logicalNode->removeChild($child);
                $logicalNode->appendChild(new ConstantNode(false));
            } elseif ($child instanceof LogicalNode) {
                $this->removeForbiddenLocalDataAttributeConditionsFromFilterRecursively($child);
            }
        }
    }

    /**
     * NOTE: Currently, local data attribute access policies are not removed from prepared filters since the attributes used in the filter
     * do not leak to the user.
     *
     * @throws ApiError
     * @throws \Exception
     */
    private function createPreparedFilter(string $preparedFilterId, string $resourceClass, array $deserializationGroups): Filter
    {
        $filterQueryString = $this->preparedFilterController->getPreparedFilterQueryString($preparedFilterId);
        if ($filterQueryString === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Prepared filter undefined.', ErrorIds::PREPARED_FILTER_UNDEFINED);
        }
        if ($this->isGranted(PreparedFilterProvider::getPolicyNameByFilterIdentifier($preparedFilterId)) === false) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Access to prepared filter denied.', ErrorIds::PREPARED_FILTER_ACCESS_DENIED);
        }

        return $this->createFilter(
            Parameters::getQueryParametersFromQueryString($filterQueryString, Parameters::FILTER), $resourceClass, $deserializationGroups, false);
    }

    /**
     * @return string[]
     */
    private function getAvailableAttributePaths(string $resourceClass, array $deserializationGroups): array
    {
        $availableAttributePaths = [];

        $propertyNamesFactoryOptions = [];
        Tools::removeValueFromArray($deserializationGroups, 'LocalData:output');
        $propertyNamesFactoryOptions['serializer_groups'] = $deserializationGroups;

        try {
            foreach ($this->propertyNameCollectionFactory->create(
                $resourceClass, $propertyNamesFactoryOptions) as $propertyName) {
                $availableAttributePaths[] = $propertyName;
            }
        } catch (ResourceClassNotFoundException $exception) {
            throw new \RuntimeException($exception->getMessage());
        }

        foreach ($this->localDataAccessChecker->getConfiguredLocalDataAttributeNames() as $localDataAttributeName) {
            $availableAttributePaths[] = self::LOCAL_DATA_BASE_PATH.$localDataAttributeName;
        }

        return $availableAttributePaths;
    }

    private function isGrantedReadAccessToLocalDataAttribute(string $localDataAttributeName): bool
    {
        $isGrantedReadAccess = $this->localDataReadAccessGrantedMap[$localDataAttributeName] ?? null;
        if ($isGrantedReadAccess === null) {
            $isGrantedReadAccess = $this->localDataAccessChecker->isGrantedReadAccess($localDataAttributeName, $this);
            $this->localDataReadAccessGrantedMap[$localDataAttributeName] = $isGrantedReadAccess;
        }

        return $isGrantedReadAccess;
    }

    private static function isLocalDataAttributePath(string $attributePath, ?string &$localDataAttributeName = null): bool
    {
        $returnValue = str_starts_with($attributePath, self::LOCAL_DATA_BASE_PATH);
        if ($returnValue && $localDataAttributeName !== null) {
            $localDataAttributeName = substr($attributePath, strlen(self::LOCAL_DATA_BASE_PATH));
        }

        return $returnValue;
    }
}
