<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

use ApiPlatform\Core\DataProvider\Pagination as ApiPlatformPagination;

class Pagination
{
    private const PAGE_PARAMETER_NAME = 'page';
    private const NUM_ITEMS_PER_PAGE_PARAMETER_NAME = 'perPage';
    private const IS_PARTIAL_PAGINATION_PARAMETER_NAME = 'partialPagination';

    private const IS_PARTIAL_PAGINATION_DEFAULT = false;

    /** @var ApiPlatformPagination */
    private $pagination;

    public function __construct(ApiPlatformPagination $pagination)
    {
        $this->pagination = $pagination;
    }

    public function addPaginationOptions(array &$options, string $resourceClass, string $operationName, array $context)
    {
        $options[self::PAGE_PARAMETER_NAME] = $this->pagination->getPage($context);
        $options[self::NUM_ITEMS_PER_PAGE_PARAMETER_NAME] = $this->pagination->getLimit($resourceClass, $operationName, $context);
        $options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] = $this->pagination->isPartialEnabled($resourceClass, $operationName, $context);
    }

    public static function isPartialPagination(array $options)
    {
        return $options[self::IS_PARTIAL_PAGINATION_PARAMETER_NAME] ?? self::IS_PARTIAL_PAGINATION_DEFAULT;
    }
}
