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
use Dbp\Relay\CoreBundle\Rest\Query\Filter\ExpressionLanguageFilterCreator;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterException;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\FilterTreeBuilder;
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
use Symfony\Component\HttpFoundation\Request;
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
    private const EXPRESSION_LANGUAGE_FILTER_CREATOR_VARIABLE_NAME = 'Filter';

    private Locale $locale;
    private PreparedFilters $preparedFiltersController;
    private LocalDataAccessChecker $localDataAccessChecker;
    private PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory;

    /** @var bool[] */
    private array $localDataReadAccessGrantedMap = [];
    private bool $areQueryFiltersEnabled = false;
    private bool $arePreparedFiltersEnabled = false;
    private bool $isSortEnabled = false;
    private ?string $mandatoryFilterExpression = null;

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
        $this->mandatoryFilterExpression = $filterConfig[Filter::ENFORCED_FILTER_CONFIG_NODE] ?? null;
        $this->preparedFiltersController->loadConfig($filterConfig);

        $this->isSortEnabled =
            $config[Rest::ROOT_CONFIG_NODE][Query::ROOT_CONFIG_NODE][Sort::ROOT_CONFIG_NODE][Sort::ENABLE_SORT_CONFIG_NODE] ?? false;

        parent::setUpAccessControlPolicies(roles: array_merge(
            $this->localDataAccessChecker->getPolicies(),
            $this->preparedFiltersController->getPolicies()));
    }

    /**
     * @throws ApiError
     */
    protected function getItemInternal(string $id, array $context): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $filters = $context[self::FILTERS_CONTEXT_KEY] ?? [];
        $options = $this->createOptions($filters);

        $item = $this->getItemById($id, $filters, $options);

        if ($item !== null) {
            $this->forbidCurrentUserToAccessItemUnlessAuthorized(self::GET_ITEM_OPERATION, $item, $filters);
            $this->addForbiddenLocalDataAttributesWithNullValue([$item], $options, $filters);
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

        $returnEmptyPage = false;
        if ($filter !== null) {
            try {
                $filter->simplify();
            } catch (FilterException $filterException) {
                throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, $filterException->getMessage());
            }
            if ($filter->isAlwaysFalse()) {
                $returnEmptyPage = true;
            } elseif (!$filter->isAlwaysTrue()) {
                Options::setFilter($options, $filter);
            }
        }

        if ($sort !== null) {
            Options::setSort($options, $sort);
        }

        $pageItems = [];
        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        if (!$returnEmptyPage) {
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
     * Indicates whether the original (root) HTTP request is a GET request.
     * Note that ApiPlatform internally calls providers during write item operations (PATCH, PUT, and DELETE)
     * to get the item to process. In those cases this method can be used to distinguish whether we are dealing
     * with a client (http) GET request (true), or an internal get item/collection request (false).
     */
    protected function isRootGetRequest(): bool
    {
        return $this->getCurrentRootRequestMethod() === Request::METHOD_GET;
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
     * @throws ApiError
     */
    private function createFilter(mixed $filterParameters, array $availableAttributePaths, bool $removeForbiddenLocalDataAttributeConditions = true): Filter
    {
        if (is_array($filterParameters) === false) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, '\''.Parameters::FILTER.'\' parameter must be an array. Square brackets missing.', ErrorIds::FILTER_PARAMETER_MUST_BE_AN_ARRAY);
        }
        try {
            $filter = FromQueryFilterCreator::createFilterFromQueryParameters(
                $filterParameters, $availableAttributePaths);
            if ($removeForbiddenLocalDataAttributeConditions) {
                $this->removeForbiddenLocalDataAttributeConditionsFromFilterRecursively($filter->getRootNode());
            }

            return $filter;
        } catch (FilterException $exception) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, $exception->getMessage(), ErrorIds::FILTER_INVALID, [$exception->getCode(), $exception->getMessage()]);
        }
    }

    /**
     * @param string|array $sortQueryParameters
     *
     * @throws ApiError
     */
    private function createSort(mixed $sortQueryParameters, array $availableAttributePaths): ?Sort
    {
        try {
            $sortFields = [];
            foreach (FromQuerySortCreator::createSortFromQueryParameters(
                $sortQueryParameters, $availableAttributePaths)->getSortFields() as $sortField) {
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
                && ($localDataAttributeName = LocalData::tryGetLocalDataAttributeName($child->getField()))
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

        $availableAttributePaths = null;
        if ($this->mandatoryFilterExpression !== null) {
            $filter = $this->createFilterFromExpression($this->mandatoryFilterExpression);
        }

        if ($this->areQueryFiltersEnabled && $filterParameter = Parameters::getFilter($filters)) {
            $queryFilter = $this->createFilter($filterParameter,
                $availableAttributePaths = $this->getAvailableAttributePaths($resourceClass, $deserializationGroups));
            try {
                $filter = $filter !== null ? $filter->combineWith($queryFilter) : $queryFilter;
            } catch (FilterException $filterException) {
                throw new \RuntimeException('combing filters with query filter failed: '.$filterException->getMessage());
            }
        }

        if ($this->arePreparedFiltersEnabled && $preparedFilterParameter = Parameters::getPreparedFilter($filters)) {
            $preparedFilter = $this->createPreparedFilter($preparedFilterParameter,
                $availableAttributePaths ??= $this->getAvailableAttributePaths($resourceClass, $deserializationGroups));
            try {
                $filter = $filter !== null ? $filter->combineWith($preparedFilter) : $preparedFilter;
            } catch (FilterException $filterException) {
                throw new \RuntimeException('combining filters with prepared filter failed: '.$filterException->getMessage());
            }
        }

        if ($this->isSortEnabled && $sortParameter = Parameters::getSort($filters)) {
            $sort = $this->createSort($sortParameter,
                $availableAttributePaths ?? $this->getAvailableAttributePaths($resourceClass, $deserializationGroups));
        }

        return [$filter, $sort];
    }

    /**
     * NOTE: Currently, local data attribute access policies are not removed from prepared filters since the attributes used in the filter
     * do not leak to the user.
     *
     * @throws ApiError
     */
    private function createPreparedFilter(string $preparedFilterId, array $availableAttributePaths): Filter
    {
        $filterQueryString = $this->preparedFiltersController->getPreparedFilterQueryString($preparedFilterId);
        if ($filterQueryString === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'Prepared filter undefined.', ErrorIds::PREPARED_FILTER_UNDEFINED);
        }
        if ($this->isGrantedRole(PreparedFilters::getPolicyNameByFilterIdentifier($preparedFilterId)) === false) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'Access to prepared filter denied.', ErrorIds::PREPARED_FILTER_ACCESS_DENIED);
        }

        return $this->createFilter(
            Parameters::getQueryParametersFromQueryString($filterQueryString, Parameters::FILTER), $availableAttributePaths, false);
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
            $availableAttributePaths[] = LocalData::getAttributePath($localDataAttributeName);
        }

        return $availableAttributePaths;
    }

    private function createFilterFromExpression(string $mandatoryFilterExpression)
    {
        try {
            $filterTree = $this->evaluateCustomExpression($mandatoryFilterExpression, [
                self::EXPRESSION_LANGUAGE_FILTER_CREATOR_VARIABLE_NAME => new ExpressionLanguageFilterCreator(),
            ]);
        } catch (\Exception $exception) {
            throw new \RuntimeException(Filter::ENFORCED_FILTER_CONFIG_NODE.' expression is invalid: '.$exception->getMessage());
        }
        if (false === ($filterTree instanceof FilterTreeBuilder)) {
            throw new \RuntimeException(
                Filter::ENFORCED_FILTER_CONFIG_NODE.' expression is invalid: must return an instance of FilterTreeBuilder');
        }
        try {
            return $filterTree->createFilter();
        } catch (FilterException $filterException) {
            throw new \RuntimeException(Filter::ENFORCED_FILTER_CONFIG_NODE.' filter is invalid: '.$filterException->getMessage());
        }
    }
}
