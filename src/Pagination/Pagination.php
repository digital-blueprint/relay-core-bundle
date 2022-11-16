<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

class Pagination
{
    private const CURRENT_PAGE_NUMBER_DEFAULT = 1;
    public const MAX_NUM_ITEMS_PER_PAGE_DEFAULT = 30;

    private const CURRENT_PAGE_NUMBER_PARAMETER_NAME = 'page';
    private const MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME = 'perPage';

    public static function getCurrentPageNumber(array $options): int
    {
        return max(1, intval($options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT));
    }

    public static function getMaxNumItemsPerPage(array $options): int
    {
        return max(1, intval($options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT));
    }
}
