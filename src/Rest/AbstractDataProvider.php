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
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FromQueryFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilterProvider;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use Exception;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\HttpFoundation\Response;

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

    protected function __construct()
    {
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
     * @throws Exception
     */
    protected function getCollectionInternal(array $context): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters, $context[self::RESOURCE_CLASS_CONTEXT_KEY], $context[self::GROUPS_CONTEXT_KEY]);

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
        $this->localDataAccessChecker->removeForbiddenLocalDataAttributeValues($pageItems, Options::getLocalDataAttributes($options), $this);

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    /**
     * @throws ApiError
     * @throws Exception
     */
    protected function getItemInternal(string $id, array $context): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters,
            $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null, $context[self::GROUPS_CONTEXT_KEY] ?? null);

        $item = $this->getItemById($id, $filters, $options);
        $this->localDataAccessChecker->removeForbiddenLocalDataAttributeValues([$item], Options::getLocalDataAttributes($options), $this);

        return $item;
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): ?object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError
     * @throws Exception
     */
    private function createOptions(array $filters, ?string $resourceClass, ?array $deserializationGroups): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        if ($includeLocalParameter = Parameters::getIncludeLocal($filters)) {
            $referencedLocalDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($includeLocalParameter);
            $this->localDataAccessChecker->assertLocalDataAttributesAreDefined($referencedLocalDataAttributes, $this);
            Options::setLocalDataAttributes($options, $referencedLocalDataAttributes);
        }

        if ($filterParameter = Parameters::getFilter($filters)) {
            Options::addFilter($options, $this->createFilter($filterParameter, $resourceClass, $deserializationGroups));
        }

        if ($preparedFilterParameter = $filters[Parameters::PREPARED_FILTER] ?? null) {
            Options::addFilter($options, $this->createPreparedFilter($preparedFilterParameter, $resourceClass, $deserializationGroups));
        }

        return $options;
    }

    /**
     * @throws ApiError
     * @throws Exception
     */
    private function createFilter($filterParameter, ?string $resourceClass, ?array $deserializationGroups): Filter
    {
        if ($resourceClass === null || $deserializationGroups === null) {
            throw new Exception('Provider context must contain \''.self::RESOURCE_CLASS_CONTEXT_KEY.'\' and \''.self::GROUPS_CONTEXT_KEY.'\' when using filters to determine available resource properties.');
        }

        if (is_array($filterParameter) === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, '\''.Parameters::FILTER.'\' parameter must be an array. Square brackets missing.', ErrorIds::FILTER_PARAMETER_MUST_BE_AN_ARRAY);
        }
        try {
            return FromQueryFilterCreator::createFilterFromQueryParameters($filterParameter, $this->getAvailableAttributePaths($resourceClass, $deserializationGroups));
        } catch (FilterException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::FILTER_INVALID, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * @throws ApiError
     * @throws Exception
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
            Parameters::getQueryParametersFromQueryString($filterQueryString, Parameters::FILTER), $resourceClass, $deserializationGroups);
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
}
