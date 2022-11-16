<?php

declare(strict_types=1);
/**
 * This is a paginator for collection data providers to work with items from an array,
 * that only contains a part of the result set.
 */

namespace Dbp\Relay\CoreBundle\Helpers;

/**
 * @deprecated Use Dbp\Relay\CoreBundle\Pagination\PartialPaginator
 */
class ArrayPartPaginator extends ArrayPaginator
{
    protected $totalItemCount = 0;

    public function __construct($items = [], $totalItemCount = 0, $page = 1, $perPage = 30)
    {
        $this->totalItemCount = $totalItemCount;
        parent::__construct($items, $page, $perPage);
    }

    /**
     * Gets the number of items in the whole collection.
     */
    public function getTotalItems(): float
    {
        return $this->totalItemCount;
    }

    /**
     * Gets the current page number.
     */
    public function getCurrentPage(): float
    {
        return $this->page;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        $value = isset($this->array[$this->position]);

        return $value;
    }
}
