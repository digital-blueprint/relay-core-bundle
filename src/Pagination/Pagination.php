<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

class Pagination
{
    public const MAX_NUM_ITEMS_PER_PAGE_DEFAULT = 30;

    private const CURRENT_PAGE_NUMBER_PARAMETER_NAME = 'page';
    private const MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME = 'perPage';
    private const IS_PARTIAL_PAGINATION_PARAMETER_NAME = 'partialPagination';

    private const CURRENT_PAGE_NUMBER_DEFAULT = 1;
    private const IS_PARTIAL_PAGINATION_DEFAULT = false;

    public static function addPaginationOptions(array &$options, array $filters, int $maxNumItemsPerPageDefault = self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT)
    {
        self::addPaginationOptionsInternal($options, $filters, $maxNumItemsPerPageDefault);
    }

    public static function getCurrentPageNumber(array $options): int
    {
        return $options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT;
    }

    public static function getMaxNumItemsPerPage(array $options): int
    {
        return $options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT;
    }

    public static function isPartialPagination(array $options): bool
    {
        return $options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] ?? self::IS_PARTIAL_PAGINATION_DEFAULT;
    }

    /**
     * Creates a new full paginator for the given page items.
     */
    public static function createFullPaginator(array $pageItems, array $options, int $totalNumItems): FullPaginator
    {
        return new FullPaginator($pageItems, self::getCurrentPageNumber($options), self::getMaxNumItemsPerPage($options), $totalNumItems);
    }

    /**
     * Creates a new partial paginator for the given page items.
     */
    public static function createPartialPaginator(array $pageItems, array $options): PartialPaginator
    {
        return new PartialPaginator($pageItems, self::getCurrentPageNumber($options), self::getMaxNumItemsPerPage($options));
    }

    /**
     * Creates a new paginator for the given whole result set.
     * Note that this is always a full paginator, even if a partial paginator was requested.
     */
    public static function createWholeResultPaginator(array $resultItems, array $options): WholeResultPaginator
    {
        return new WholeResultPaginator($resultItems, self::getCurrentPageNumber($options), self::getMaxNumItemsPerPage($options));
    }

    private static function addPaginationOptionsInternal(array &$options, array $filters, int $maxNumItemsPerPageDefault)
    {
        if (($currentPageNumber = $filters[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT) !== self::CURRENT_PAGE_NUMBER_DEFAULT) {
            $options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] = $currentPageNumber;
        }
        if (($maxNumItemsPerPage = $filters[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? $maxNumItemsPerPageDefault) !== self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT) {
            $options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] = $maxNumItemsPerPage;
        }
        if (($isPartialPagination = $filters[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] ?? self::IS_PARTIAL_PAGINATION_DEFAULT) !== self::IS_PARTIAL_PAGINATION_DEFAULT) {
            $options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] = $isPartialPagination;
        }
    }
}
