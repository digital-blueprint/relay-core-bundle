<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest;

use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProviderInterface;
use Dbp\Relay\CoreBundle\ApiPlatform\State\StateProviderTrait;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\LocalData\LocalDataAccessChecker;
use Dbp\Relay\CoreBundle\Locale\Locale;
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

    /** @var Locale */
    private $locale;

    /** @var PreparedFilterProvider */
    private $preparedFilterController;

    /** @var LocalDataAccessChecker */
    private $localDataAccessChecker;

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
     * @throws ApiError|Exception
     */
    protected function getCollectionInternal(array $filters = []): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $options = $this->createOptions($filters);

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
        $this->localDataAccessChecker->enforceLocalDataAccessControlPolicies($pageItems, $options, $this);

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    /**
     * @throws ApiError|Exception
     */
    protected function getItemInternal(string $id, array $filters = []): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $options = $this->createOptions($filters);

        $item = $this->getItemById($id, $filters, $options);
        if ($item !== null) {
            $this->localDataAccessChecker->enforceLocalDataAccessControlPolicies([$item], $options, $this);
        }

        return $item;
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): ?object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError|Exception
     */
    private function createOptions(array $filters): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        if ($filterParameter = Parameters::getIncludeLocal($filters)) {
            $localDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($filterParameter);
            Options::setLocalDataAttributes($options, $localDataAttributes);
            $this->localDataAccessChecker->checkRequestedLocalDataAttributes($localDataAttributes);
        }
        if ($filterParameter = Parameters::getFilter($filters)) {
            if (is_array($filterParameter) === false) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, Parameters::FILTER.' parameter key lacks square brackets', ErrorIds::FILTER_INVALID_FILTER_KEY_SQUARE_BRACKETS_MISSING);
            }
            try {
                Options::addFilter($options, FromQueryFilterCreator::createFilterFromQueryParameters($filterParameter));
            } catch (Exception $exception) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'invalid filter: '.$exception->getMessage(), ErrorIds::FILTER_INVALID);
            }
        }
        if ($preparedFilterId = $filters[Parameters::PREPARED_FILTER] ?? null) {
            $filter = $this->preparedFilterController->getPreparedFilterById($preparedFilterId);
            if ($filter === null) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'prepared filter undefined', ErrorIds::PREPARED_FILTER_UNDEFINED);
            }
            if ($this->isGranted(PreparedFilterProvider::getPolicyNameByFilterId($preparedFilterId)) === false) {
                throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'prepared filter access denied', ErrorIds::PREPARED_FILTER_ACCESS_DENIED);
            }
            Options::addFilter($options, $filter);
        }

        return $options;
    }
}
