<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Pagination;

class Pagination
{
    public const MAX_NUM_ITEMS_PER_PAGE_DEFAULT = 30;

    public const CURRENT_PAGE_NUMBER_PARAMETER_NAME = 'page';
    public const MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME = 'perPage';

    private const CURRENT_PAGE_NUMBER_DEFAULT = 1;

    public static function getCurrentPageNumber(array $options): int
    {
        return max(1, intval(
            $options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT));
    }

    public static function getMaxNumItemsPerPage(array $options,
        int $maxNumItemPerPageDefault = self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT): int
    {
        return max(1, intval(
            $options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? $maxNumItemPerPageDefault));
    }

    public static function getFirstItemIndex(int $currentPageNumber, int $maxNumItemsPerPage): int
    {
        return max(0, ($currentPageNumber - 1) * $maxNumItemsPerPage);
    }

    public static function getPage(int $firstResultIndex, int $maxNumResults, callable $getItemsCallback,
        callable $passesFilterCallback, int $maxNumItemsToGet = 1024, bool $preserveItemKeys = false): array
    {
        $resultPageItems = [];
        if ($maxNumResults > 0) {
            $firstItemIndexToGet = 0;
            $done = false;
            $currentFilteredItemIndex = 0;
            while (!$done && ($items = $getItemsCallback($firstItemIndexToGet, $maxNumItemsToGet)) !== []) {
                foreach ($items as $key => $item) {
                    if ($passesFilterCallback($item)) {
                        if ($currentFilteredItemIndex >= $firstResultIndex) {
                            if ($preserveItemKeys) {
                                $resultPageItems[$key] = $item;
                            } else {
                                $resultPageItems[] = $item;
                            }
                        }
                        if (count($resultPageItems) === $maxNumResults) {
                            $done = true;
                            break;
                        }
                        ++$currentFilteredItemIndex;
                    }
                }
                $firstItemIndexToGet += $maxNumItemsToGet;
            }
        }

        return $resultPageItems;
    }

    /**
     * @param callable(int $currentPageStartIndex, int $maxNumItemsPerPage): array $getPageCallback
     */
    public static function getAllResults(callable $getPageCallback, int $maxNumItemsPerPage = 1024): array
    {
        $allResults = [];
        $currentPageStartIndex = 0;
        do {
            $resultPage = $getPageCallback($currentPageStartIndex, $maxNumItemsPerPage);
            $allResults = array_merge($allResults, $resultPage);
            $currentPageStartIndex += $maxNumItemsPerPage;
        } while (count($resultPage) === $maxNumItemsPerPage);

        return $allResults;
    }
}
