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
     * @deprecated Use self::getAllResultsPageStartIndexBased
     *
     * @param callable(int $currentPageStartIndex, int $maxNumItemsPerPage): iterable $getPageCallback
     */
    public static function getAllResults(callable $getPageCallback, int $maxNumItemsPerPage = 1024): array
    {
        return iterator_to_array(self::getAllResultsPageStartIndexBased($getPageCallback, $maxNumItemsPerPage));
    }

    /**
     * Returns an iterator over all results given a (page-number-based) get-page callback. Page numbers start from 1.
     *
     * @param callable(int $currentPageNumber, int $maxNumItemsPerPage): iterable $getPageByPageNumberCallback
     */
    public static function getAllResultsPageNumberBased(callable $getPageByPageNumberCallback, int $maxNumItemsPerPage = 1024): \Generator
    {
        $currentPageNumber = 1;
        do {
            $numPageItems = 0;
            foreach ($getPageByPageNumberCallback($currentPageNumber, $maxNumItemsPerPage) as $resulItem) {
                ++$numPageItems;
                yield $resulItem;
            }
            ++$currentPageNumber;
        } while ($numPageItems >= $maxNumItemsPerPage);
    }

    /**
     * Returns an iterator over all results given a (page-start-index-based) get-page callback. Page indices start from 0.
     *
     * @param callable(int $currentPageStartIndex, int $maxNumItemsPerPage): array $getPageByPageStartIndexCallback
     */
    public static function getAllResultsPageStartIndexBased(callable $getPageByPageStartIndexCallback, int $maxNumItemsPerPage = 1024): \Generator
    {
        $currentPageStartIndex = 0;
        do {
            $numPageItems = 0;
            foreach ($getPageByPageStartIndexCallback($currentPageStartIndex, $maxNumItemsPerPage) as $resulItem) {
                ++$numPageItems;
                yield $resulItem;
            }
            $currentPageStartIndex += $maxNumItemsPerPage;
        } while ($numPageItems >= $maxNumItemsPerPage);
    }
}
