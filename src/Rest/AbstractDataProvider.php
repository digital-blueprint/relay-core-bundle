<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
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
use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilters;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use Dbp\Relay\CoreBundle\Rest\Query\Query;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\FromQuerySortCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\Sort;
use Dbp\Relay\CoreBundle\Rest\Query\Sort\SortException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

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
    protected const NO_ID = '';

    private const FILTERS_CONTEXT_KEY = 'filters';
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';

    private Locale $locale;
    private PreparedFilters $preparedFiltersController;
    private LocalDataAccessChecker $localDataAccessChecker;
    private PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory;

    /** @var bool[] */
    private array $localDataReadAccessGrantedMap = [];
    private bool $areQueryFiltersEnabled = false;
    private bool $arePreparedFiltersEnabled = false;
    private bool $isSortEnabled = false;
    private ?array $availableAttributePaths = null;

    /**
     * @deprecated Use self::appendConfigNodeDefinitions instead
     */
    public static function getLocalDataConfigNodeDefinition(): NodeDefinition
    {
        return LocalDataAccessChecker::getConfigNodeDefinition();
    }

    /**
     * @deprecated Since version v0.1.175. Append LocalData::getConfigNodeDefinition() and/or Rest::getConfigNodeDefinition()
     *             to add local data config and/or REST config definition instead.
     */
    public static function appendConfigNodeDefinitions(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->append(LocalData::getConfigNodeDefinition());
        $rootNode->append(Rest::getConfigNodeDefinition());
    }

    public function __construct()
    {
        parent::__construct();

        $this->preparedFiltersController = new PreparedFilters();
        $this->localDataAccessChecker = new LocalDataAccessChecker();
    }

    #[Required]
    public function __injectLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    #[Required]
    public function __injectPropertyNameCollectionFactory(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory): void
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
    }

    public function setConfig(array $config): void
    {
        parent::setConfig($config);

        $this->localDataAccessChecker->loadConfig($config);

        $filterConfig = $config[Rest::ROOT_CONFIG_NODE][Query::ROOT_CONFIG_NODE][Filter::ROOT_CONFIG_NODE] ?? [];
        $this->areQueryFiltersEnabled = $filterConfig[Filter::ENABLE_QUERY_FILTERS_CONFIG_NODE] ?? false;
        $this->arePreparedFiltersEnabled = $filterConfig[Filter::ENABLE_PREPARED_FILTERS_CONFIG_NODE] ?? false;
        $this->preparedFiltersController->loadConfig($filterConfig);

        $this->isSortEnabled =
            $config[Rest::ROOT_CONFIG_NODE][Query::ROOT_CONFIG_NODE][Sort::ROOT_CONFIG_NODE][Sort::ENABLE_SORT_CONFIG_NODE] ?? false;

        parent::setUpAccessControlPolicies(roles: $this->localDataAccessChecker->getPolicies());
    }

    /**
     * @throws ApiError
     */
    protected function getItemInternal(string $id, array $context): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters);

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null;
        $deserializationGroups = $context[self::GROUPS_CONTEXT_KEY] ?? [];

        $getAvailableAttributePaths = function () use ($resourceClass, $deserializationGroups): array {
            return $resourceClass !== null ?
                $this->getAvailableAttributePaths($resourceClass, $deserializationGroups) :
                [];
        };
        $preparedFilter = $this->createPreparedFilter(null, $getAvailableAttributePaths);
        if ($preparedFilter !== null) {
            $this->setFilterOption($options, $preparedFilter);
        }

        $item = null;
        if (true !== $preparedFilter?->isAlwaysFalse()) {
            $item = $this->getItemById($id, $filters, $options);
            if ($item !== null) {
                $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::GET_ITEM_OPERATION, $item, $filters);
                $this->addForbiddenLocalDataAttributesWithNullValue([$item], $options, $filters);
            }
        }

        return $item;
    }

    /**
     * @throws ApiError
     */
    protected function getCollectionInternal(array $context): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $this->forbidCurrentUserToGetCollectionUnlessAuthorized($filters);

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY];
        $deserializationGroups = $context[self::GROUPS_CONTEXT_KEY] ?? [];

        $options = $this->createOptions($filters);

        [$filter, $sort] = $this->getFilterAndSort($filters, $resourceClass, $deserializationGroups);

        if ($filter !== null) {
            $this->setFilterOption($options, $filter);
        }
        if ($sort !== null) {
            Options::setSort($options, $sort);
        }

        $pageItems = [];
        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        if (true !== $filter?->isAlwaysFalse()) {
            $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
            $this->addForbiddenLocalDataAttributesWithNullValue($pageItems, $options, $filters);
        }

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): ?object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * Override if you want to restrict access to the get collection operation based on the given set of filters.
     * if you want to completely forbid access to the get collection, use @see isCurrentUserGrantedOperationAccess instead
     * Returning false causes a 403 forbidden error to be thrown.
     * Defaults to true.
     */
    protected function isCurrentUserAuthorizedToGetCollection(array $filters): bool
    {
        return true;
    }

    protected function isGrantedReadAccessToLocalDataAttribute(string $localDataAttributeName): bool
    {
        $isGrantedReadAccess = $this->localDataReadAccessGrantedMap[$localDataAttributeName] ?? null;
        if ($isGrantedReadAccess === null) {
            $isGrantedReadAccess = $this->localDataAccessChecker->isGrantedReadAccess($localDataAttributeName, $this);
            $this->localDataReadAccessGrantedMap[$localDataAttributeName] = $isGrantedReadAccess;
        }

        return $isGrantedReadAccess;
    }

    /**
     * @throws ApiError
     */
    private function createOptions(array $filters): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        if ($includeLocalParameter = Parameters::getIncludeLocal($filters)) {
            $requestedLocalDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($includeLocalParameter);
            $this->localDataAccessChecker->assertLocalDataAttributesAreDefined($requestedLocalDataAttributes);
            $localDataAttributesIsGrantedReadAccess = $this->getLocalDataAttributesIsGrantedReadAccess($requestedLocalDataAttributes);
            Options::setLocalDataAttributes($options, $localDataAttributesIsGrantedReadAccess);
        }

        return $options;
    }

    /**
     * @param callable (): array $getAvailableAttributePaths
     *
     * @throws ApiError
     */
    private function createFilter(mixed $filterParameters, callable $getAvailableAttributePaths, bool $removeForbiddenLocalDataAttributeConditions = true): Filter
    {
        if (is_array($filterParameters) === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, '\''.Parameters::FILTER.'\' parameter must be an array. Square brackets missing.', ErrorIds::FILTER_PARAMETER_MUST_BE_AN_ARRAY);
        }
        try {
            $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
                $filterParameters, $getAvailableAttributePaths());
            if ($removeForbiddenLocalDataAttributeConditions) {
                $this->removeForbiddenLocalDataAttributeConditionsFromFilterRecursively($filter->getRootNode());
            }

            return $filter;
        } catch (FilterException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::FILTER_INVALID, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * @param string|array       $sortQueryParameters
     * @param callable (): array $getAvailableAttributePaths
     *
     * @throws ApiError
     */
    private function createSort(mixed $sortQueryParameters, callable $getAvailableAttributePaths): ?Sort
    {
        try {
            $sortFields = [];
            foreach (FromQuerySortCreator::createSortFromQueryParameters(
                $sortQueryParameters, $getAvailableAttributePaths())->getSortFields() as $sortField) {
                if (($localDataAttributeName = LocalData::tryGetLocalDataAttributeName(Sort::getPath($sortField))) === null
                    || $this->isGrantedReadAccessToLocalDataAttribute($localDataAttributeName)) {
                    $sortFields[] = $sortField;
                }
            }

            return !empty($sortFields) ? new Sort($sortFields) : null;
        } catch (SortException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::SORT_INVALID, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    private function forbidCurrentUserToGetCollectionUnlessAuthorized(array $filters): void
    {
        if (!$this->isCurrentUserAuthorizedToGetCollection($filters)) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'forbidden');
        }
    }

    private function getLocalDataAttributesIsGrantedReadAccess(array $requestedLocalDataAttributeNames): array
    {
        $localDataAttributes = [];
        foreach ($requestedLocalDataAttributeNames as $localDataAttributeName) {
            if ($this->isGrantedReadAccessToLocalDataAttribute($localDataAttributeName)) {
                $localDataAttributes[] = $localDataAttributeName;
            }
        }

        return $localDataAttributes;
    }

    private function removeForbiddenLocalDataAttributeConditionsFromFilterRecursively(LogicalNode $logicalNode): void
    {
        // TODO: replace by Filter::mapConditionNode
        foreach ($logicalNode->getChildren() as $child) {
            if ($child instanceof ConditionNode
                && ($localDataAttributeName = LocalData::tryGetLocalDataAttributeName($child->getPath()))
                && !$this->isGrantedReadAccessToLocalDataAttribute($localDataAttributeName)) {
                $logicalNode->removeChild($child);
                $logicalNode->appendChild(new ConstantNode(false));
            } elseif ($child instanceof LogicalNode) {
                $this->removeForbiddenLocalDataAttributeConditionsFromFilterRecursively($child);
            }
        }
    }

    private function addForbiddenLocalDataAttributesWithNullValue(array $items, array $options, array $filters): void
    {
        if ($includeLocalParameter = Parameters::getIncludeLocal($filters)) {
            $requestedLocalDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($includeLocalParameter);
            $localDataAttributesGrantedReadAccess = Options::getLocalDataAttributes($options);

            foreach (array_diff($requestedLocalDataAttributes, $localDataAttributesGrantedReadAccess) as $forbiddenLocalDataAttribute) {
                foreach ($items as $item) {
                    if ($item instanceof LocalDataAwareInterface) {
                        $item->setLocalDataValue($forbiddenLocalDataAttribute, null);
                    }
                }
            }
        }
    }

    private function getFilterAndSort(array $filters, string $resourceClass, array $deserializationGroups): array
    {
        $filter = null;
        $sort = null;
        $getAvailableAttributePaths = function () use ($resourceClass, $deserializationGroups): array {
            return $this->getAvailableAttributePaths($resourceClass, $deserializationGroups);
        };

        if ($filterParameter = Parameters::getFilter($filters)) {
            if ($this->areQueryFiltersEnabled === false) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'query filters are disabled',
                    ErrorIds::QUERY_FILTERS_DISABLED);
            }
            $filter = $this->createFilter($filterParameter, $getAvailableAttributePaths);
        }

        $requestedFilterIdentifier = Parameters::getPreparedFilter($filters);
        if ($requestedFilterIdentifier !== null && $this->arePreparedFiltersEnabled === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'prepared filters are disabled',
                ErrorIds::PREPARED_FILTERS_DISABLED);
        }

        $preparedFilter = $this->createPreparedFilter($requestedFilterIdentifier, $getAvailableAttributePaths);
        if ($preparedFilter !== null) {
            try {
                $filter = $filter !== null ? $filter->combineWith($preparedFilter) : $preparedFilter;
            } catch (FilterException $filterException) {
                throw new \RuntimeException('combining filters with prepared filter failed: '.$filterException->getMessage());
            }
        }

        if ($sortParameter = Parameters::getSort($filters)) {
            if ($this->isSortEnabled === false) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'sort is disabled', ErrorIds::SORT_DISABLED);
            }
            $sort = $this->createSort($sortParameter, $getAvailableAttributePaths);
        }

        return [$filter, $sort];
    }

    /**
     * Creates a combined filter from an optional requested (prepared) filter and
     * the list of filters whose usage is forced for the current user by combining them with
     * logical AND (&&).
     * NOTE: Currently, local data attribute access policies are not removed from prepared filters since the attributes used in the filter
     * do not leak to the user.
     *
     * @param callable (): array $getAvailableAttributePaths
     *
     * @throws ApiError
     */
    private function createPreparedFilter(?string $requestedFilterIdentifier, callable $getAvailableAttributePaths): ?Filter
    {
        $filtersToApplyIdentifiers = [];

        if ($requestedFilterIdentifier !== null) {
            if (false === $this->preparedFiltersController->isPreparedFilterDefined($requestedFilterIdentifier)) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST,
                    'Prepared filter undefined', ErrorIds::PREPARED_FILTER_UNDEFINED);
            }
            if (false === $this->evaluateCustomExpression(
                $this->preparedFiltersController->getUsePolicies()[$requestedFilterIdentifier])) {
                throw ApiError::withDetails(Response::HTTP_FORBIDDEN,
                    'Access to prepared filter denied', ErrorIds::PREPARED_FILTER_ACCESS_DENIED);
            }

            $filtersToApplyIdentifiers[] = $requestedFilterIdentifier;
        }

        foreach ($this->preparedFiltersController->getForceUsePolicies() as $filterIdentifier => $forceUsePolicy) {
            if (true === $this->evaluateCustomExpression($forceUsePolicy)) {
                $filtersToApplyIdentifiers[] = $filterIdentifier;
            }
        }

        $filtersToApplyIdentifiers = array_unique($filtersToApplyIdentifiers);

        /* @var Filter $combinedFilter */
        $combinedFilter = null;
        foreach ($filtersToApplyIdentifiers as $filterIdentifier) {
            $filterQueryString = $this->preparedFiltersController->getPreparedFilterQueryString($filterIdentifier);

            $filter = $this->createFilter(
                Parameters::getQueryParametersFromQueryString($filterQueryString, Parameters::FILTER),
                $getAvailableAttributePaths, false);
            try {
                $combinedFilter = $combinedFilter === null ? $filter : $combinedFilter->combineWith($filter);
            } catch (FilterException $filterException) {
                throw new \RuntimeException('combining prepared filters failed: '.$filterException->getMessage());
            }
        }

        return $combinedFilter;
    }

    /**
     * @return string[]
     */
    private function getAvailableAttributePaths(string $resourceClass, array $deserializationGroups): array
    {
        if ($this->availableAttributePaths === null) {
            $this->availableAttributePaths = [];

            $propertyNamesFactoryOptions = [];
            Tools::removeValueFromArray($deserializationGroups, 'LocalData:output');
            $propertyNamesFactoryOptions['serializer_groups'] = $deserializationGroups;

            try {
                foreach ($this->propertyNameCollectionFactory->create(
                    $resourceClass, $propertyNamesFactoryOptions) as $propertyName) {
                    $this->availableAttributePaths[] = $propertyName;
                }
            } catch (ResourceClassNotFoundException $exception) {
                throw new \RuntimeException($exception->getMessage());
            }

            foreach ($this->localDataAccessChecker->getConfiguredLocalDataAttributeNames() as $localDataAttributeName) {
                $this->availableAttributePaths[] = LocalData::getAttributePath($localDataAttributeName);
            }
        }

        return $this->availableAttributePaths;
    }

    private function setFilterOption(array &$options, Filter $filter): void
    {
        try {
            $filter->simplify();
        } catch (FilterException $filterException) {
            throw new \RuntimeException($filterException->getMessage());
        }
        if (false === $filter->isAlwaysTrue()) {
            Options::setFilter($options, $filter);
        }
    }
}
