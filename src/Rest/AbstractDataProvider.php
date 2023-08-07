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
     */
    protected function getCollectionInternal(array $context): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters, $context[self::RESOURCE_CLASS_CONTEXT_KEY], $context[self::GROUPS_CONTEXT_KEY]);

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    /**
     * @throws ApiError
     */
    protected function getItemInternal(string $id, array $context): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters, $context[self::RESOURCE_CLASS_CONTEXT_KEY], $context[self::GROUPS_CONTEXT_KEY]);

        return $this->getItemById($id, $filters, $options);
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): ?object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError
     */
    private function createOptions(array $filters, string $resourceClass, array $denormalizationGroups): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        $referencedLocalDataAttributes = [];
        if ($includeLocalParameter = Parameters::getIncludeLocal($filters)) {
            $referencedLocalDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($includeLocalParameter);
            Options::setLocalDataAttributes($options, $referencedLocalDataAttributes);
        }

        if ($filterParameter = Parameters::getFilter($filters)) {
            $usedAttributePaths = [];
            Options::addFilter($options, $this->createFilter($filterParameter, $resourceClass, $denormalizationGroups, $usedAttributePaths));
            $basePathLength = strlen(self::LOCAL_DATA_BASE_PATH);
            $referencedLocalDataAttributes = array_unique(array_merge($referencedLocalDataAttributes,
                Tools::arrayFilterAndMap($usedAttributePaths,
                    function ($attributePath) {
                        return str_starts_with($attributePath, self::LOCAL_DATA_BASE_PATH);
                    },
                    function ($attributePath) use ($basePathLength) {
                        return substr($attributePath, $basePathLength);
                    })));
        }

        if ($preparedFilterParameter = $filters[Parameters::PREPARED_FILTER] ?? null) {
            Options::addFilter($options, $this->createPreparedFilter($preparedFilterParameter, $resourceClass, $denormalizationGroups));
        }

        $this->localDataAccessChecker->denyAccessUnlessIsGrantedReadAccess($referencedLocalDataAttributes, $this);

        return $options;
    }

    /**
     * @throws ApiError
     */
    private function createFilter($filterParameter, string $resourceClass, array $denormalizationGroups, array &$usedAttributePaths = null): Filter
    {
        if (is_array($filterParameter) === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, Parameters::FILTER.' parameter key lacks square brackets', ErrorIds::FILTER_INVALID_FILTER_KEY_SQUARE_BRACKETS_MISSING);
        }
        try {
            return FromQueryFilterCreator::createFilterFromQueryParameters($filterParameter, $this->getAvailableAttributePaths($resourceClass, $denormalizationGroups), $usedAttributePaths);
        } catch (FilterException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::FILTER_INVALID);
        }
    }

    /**
     * @throws ApiError
     */
    private function createPreparedFilter(string $preparedFilterId, string $resourceClass, array $denormalizationGroups): Filter
    {
        $filterQueryString = $this->preparedFilterController->getPreparedFilterQueryString($preparedFilterId);
        if ($filterQueryString === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'prepared filter undefined', ErrorIds::PREPARED_FILTER_UNDEFINED);
        }
        if ($this->isGranted(PreparedFilterProvider::getPolicyNameByFilterIdentifier($preparedFilterId)) === false) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'prepared filter access denied', ErrorIds::PREPARED_FILTER_ACCESS_DENIED);
        }

        return $this->createFilter(
            Parameters::getQueryParametersFromQueryString($filterQueryString, Parameters::FILTER), $resourceClass, $denormalizationGroups);
    }

    /**
     * @return string[]
     */
    private function getAvailableAttributePaths(string $resourceClass, array $denormalizationGroups): array
    {
        $availableAttributePaths = [];

        $propertyNamesFactoryOptions = [];
        Tools::removeValueFromArray($denormalizationGroups, 'LocalData:output');
        $propertyNamesFactoryOptions['serializer_groups'] = $denormalizationGroups;

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
