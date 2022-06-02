<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

use ApiPlatform\Core\DataProvider\Pagination as ApiPlatformPagination;

class Pagination
{
    private const CURRENT_PAGE_NUMBER_PARAMETER_NAME = 'page';
    private const MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME = 'perPage';
    private const IS_PARTIAL_PAGINATION_PARAMETER_NAME = 'partialPagination';

    private const CURRENT_PAGE_NUMBER_DEFAULT = 1;
    private const MAX_NUM_ITEMS_PER_PAGE_DEFAULT = 30;
    private const IS_PARTIAL_PAGINATION_DEFAULT = false;

    /** @var ApiPlatformPagination */
    private $pagination;

    public function __construct(ApiPlatformPagination $pagination)
    {
        $this->pagination = $pagination;
    }

    public function addPaginationOptions(array &$options, string $resourceClass, string $operationName, array $context)
    {
        $this->addPaginationOptionsInternal($options, $resourceClass, $operationName, $context);
    }

    public static function getCurrentPageNumber(array $paginationOptions): int
    {
        return $paginationOptions[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] ?? self::CURRENT_PAGE_NUMBER_DEFAULT;
    }

    public static function getMaxNumItemsPerPage(array $paginationOptions): int
    {
        return $paginationOptions[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] ?? self::MAX_NUM_ITEMS_PER_PAGE_DEFAULT;
    }

    public static function isPartialPagination(array $paginationOptions): bool
    {
        return $paginationOptions[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] ?? self::IS_PARTIAL_PAGINATION_DEFAULT;
    }

    public function createWholeResultPaginator(array $result, string $resourceClass, string $operationName, array $context): WholeResultPaginator
    {
        $paginationOptions = [];
        $this->addPaginationOptionsInternal($paginationOptions, $resourceClass, $operationName, $context);

        return new WholeResultPaginator($result, self::getCurrentPageNumber($paginationOptions), self::getMaxNumItemsPerPage($paginationOptions));
    }

    public function addPaginationOptionsInternal(array &$options, string $resourceClass, string $operationName, array $context)
    {
        $options[self::CURRENT_PAGE_NUMBER_PARAMETER_NAME] = $this->pagination->getPage($context);
        $options[self::MAX_NUM_ITEMS_PER_PAGE_PARAMETER_NAME] = $this->pagination->getLimit($resourceClass, $operationName, $context);
        $options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] = $this->pagination->isPartialEnabled($resourceClass, $operationName, $context);
    }
}
