<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HttpOperations;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\LocalData\AbstractLocalDataAuthorizationService;
use Dbp\Relay\CoreBundle\LocalData\LocalData;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Query\Filter\Filter;

abstract class AbstractDataProvider extends AbstractLocalDataAuthorizationService
{
    use DataOperationTrait;

    protected const GET_COLLECTION_OPERATION = 1;
    protected const GET_ITEM_OPERATION = 2;

    private const FILTER_PARAMETER_NAME = 'filter';

    /** @var Locale */
    private $locale;

    /**
     * @required
     */
    public function __injectLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    protected function getCollectionInternal(array $filters = []): PartialPaginator
    {
        $this->denyOperationAccessUnlessGranted(self::GET_COLLECTION_OPERATION);

        $options = $this->createOptions($filters);

        $currentPageNumber = Pagination::getCurrentPageNumber($filters);
        $maxNumItemsPerPage = Pagination::getMaxNumItemsPerPage($filters);

        $pageItems = $this->getPage($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
        $pageItems = $this->enforceLocalDataAccessControlPolicies($pageItems, $options);

        return new PartialPaginator($pageItems, $currentPageNumber, $maxNumItemsPerPage);
    }

    protected function getItemInternal($id, array $filters = []): ?object
    {
        $this->denyOperationAccessUnlessGranted(self::GET_ITEM_OPERATION);

        $options = $this->createOptions($filters);

        $item = $this->getItemById($id, $filters, $options);
        $items = $this->enforceLocalDataAccessControlPolicies([$item], $options);

        return $items[0] ?? null;
    }

    abstract protected function getItemById($id, array $filters = [], array $options = []): object;

    abstract protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array;

    /**
     * @throws ApiError
     */
    private function createOptions(array $filters): array
    {
        $options = [];
        $options[Options::LANGUAGE_OPTION] = $this->locale->getCurrentPrimaryLanguage();

        LocalData::addOptions($options, $filters);
        $this->checkRequestedLocalDataAttributes($options);

        if ($filterParameter = $filters[self::FILTER_PARAMETER_NAME] ?? null) {
            $options[Options::FILTER_OPTION] = Filter::createFromQueryParameter($filterParameter);
        }

        return $options;
    }
}
