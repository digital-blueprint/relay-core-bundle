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

    public static function getFirstItemIndex(int $currentPageNumber, int $maxNumItemsPerPage)
    {
        return max(0, ($currentPageNumber - 1) * $maxNumItemsPerPage);
    }
}
