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
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

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

    protected function getItemInternal(string $id, array $filters = []): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $options = $this->createOptions($filters);

        $item = $this->getItemById($id, $filters, $options);
        $this->localDataAccessChecker->enforceLocalDataAccessControlPolicies([$item], $options, $this);

        return $item;
    }

    abstract protected function getItemById(string $id, array $filters = [], array $options = []): object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError|\Exception
     */
    private function createOptions(array $filters): array
    {
        $options = [];

        Options::setLanguage($options, $this->locale->getCurrentPrimaryLanguage());

        if ($filterParameter = $filters[Parameters::INCLUDE_LOCAL] ?? null) {
            $localDataAttributes = LocalData::getLocalDataAttributesFromQueryParameter($filterParameter);
            Options::setLocalDataAttributes($options, $localDataAttributes);
            $this->localDataAccessChecker->checkRequestedLocalDataAttributes($localDataAttributes);
        }
        if ($filterParameter = $filters[Parameters::FILTER] ?? null) {
            Options::addFilter($options, FromQueryFilterCreator::createFilterFromQueryParameters($filterParameter));
        }
        if ($preparedFilterParameter = $filters[Parameters::PREPARED_FILTER] ?? null) {
            Options::addFilter($options, $this->preparedFilterController->getPreparedFilterById($preparedFilterParameter));
        }

        return $options;
    }
}
