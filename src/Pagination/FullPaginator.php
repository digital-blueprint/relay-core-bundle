<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;

/**
 * Paginator that holds the items of one page and is aware of the total amount of result items.
 */
class FullPaginator extends Paginator implements PaginatorInterface
{
    /** @var int */
    protected $totalNumItems;

    /**
     * @throws ApiError
     */
    public function __construct(array $items, int $page, int $numItemsPerPage, int $totalNumItems)
    {
        if ($totalNumItems < 0) {
            throw new ApiError(500, 'total number of items must be greater than or equal to zero');
        }

        parent::__construct($items, $page, $numItemsPerPage);

        $this->totalNumItems = $totalNumItems;
    }

    /**
     * Gets the last page number (page numbering starts at 1).
     */
    public function getLastPage(): float
    {
        return ceil($this->totalNumItems / $this->maxNumItemsPerPage);
    }

    /**
     * Gets the number of items in the whole result collection.
     */
    public function getTotalItems(): float
    {
        return $this->totalNumItems;
    }
}
