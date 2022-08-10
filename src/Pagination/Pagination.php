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

    public static function addOptions(array &$targetOptions, array $sourceOptions, int $maxNumItemsPerPageDefault = self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT)
    {
        $targetOptions[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] = intval($sourceOptions[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT);
        $targetOptions[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] = intval($sourceOptions[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? $maxNumItemsPerPageDefault);
        $targetOptions[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] = filter_var(
            $sourceOptions[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] ?? self::IS_PARTIAL_PAGINATION_DEFAULT, FILTER_VALIDATE_BOOLEAN);
    }

    public static function removeOptions(array &$options)
    {
        unset($options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME]);
        unset($options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME]);
        unset($options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME]);
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
}
