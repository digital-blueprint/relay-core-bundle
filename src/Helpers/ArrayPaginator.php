<?php

declare(strict_types=1);
/**
 * This is an abstract paginator for collection data providers to work with items from an array,
 * that contains the full result set.
 */

namespace DBP\API\CoreBundle\Helpers;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Iterator;

abstract class ArrayPaginator implements Iterator, PaginatorInterface
{
    protected $position = 0;
    protected $array = [];
    protected $perPage = 100;
    protected $page = 1;

    public function __construct($items = [], $page = 1, $perPage = 30)
    {
        $this->array = $items;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->rewind();
    }

    /**
     * Gets last page.
     */
    public function getLastPage(): float
    {
        $value = ceil($this->getTotalItems() / $this->perPage);

        return $value;
    }

    /**
     * Gets the number of items in the whole collection.
     */
    public function getTotalItems(): float
    {
        $value = $this->count();

        return $value;
    }

    /**
     * Gets the current page number.
     */
    public function getCurrentPage(): float
    {
        return $this->page;
    }

    /**
     * Gets the number of items by page.
     */
    public function getItemsPerPage(): float
    {
        return $this->perPage;
    }

    public function count()
    {
        $value = count($this->array);

        return $value;
    }

    public function rewind()
    {
        $this->position = ($this->page - 1) * $this->perPage;
    }

    public function current()
    {
        return $this->array[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        $value = isset($this->array[$this->position]) &&
            ($this->position >= (($this->page - 1) * $this->perPage)) &&
            ($this->position < ($this->page * $this->perPage));

        return $value;
    }
}
