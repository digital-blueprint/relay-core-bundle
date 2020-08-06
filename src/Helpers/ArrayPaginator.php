<?php
/**
 * This is an abstract paginator for collection data providers to work with items from an array,
 * that contains the full result set.
 */

namespace DBP\API\CoreBundle\Helpers;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Iterator;

abstract class ArrayPaginator implements Iterator, PaginatorInterface
{
    public const DEBUG = false;
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
        if (self::DEBUG) {
            var_dump(__METHOD__, $value);
        }

        return $value;
    }

    /**
     * Gets the number of items in the whole collection.
     */
    public function getTotalItems(): float
    {
        $value = $this->count();
        if (self::DEBUG) {
            var_dump(__METHOD__, $value);
        }

        return $value;
    }

    /**
     * Gets the current page number.
     */
    public function getCurrentPage(): float
    {
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->page);
        }

        return $this->page;
    }

    /**
     * Gets the number of items by page.
     */
    public function getItemsPerPage(): float
    {
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->perPage);
        }

        return $this->perPage;
    }

    public function count()
    {
        $value = count($this->array);
        if (self::DEBUG) {
            var_dump(__METHOD__, $value);
        }

        return $value;
    }

    public function rewind()
    {
        $this->position = ($this->page - 1) * $this->perPage;
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->position);
        }
    }

    public function current()
    {
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->position);
        }

        return $this->array[$this->position];
    }

    public function key()
    {
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->position);
        }

        return $this->position;
    }

    public function next()
    {
        ++$this->position;
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->position);
        }
    }

    public function valid()
    {
        $value = isset($this->array[$this->position]) &&
            ($this->position >= (($this->page - 1) * $this->perPage)) &&
            ($this->position < ($this->page * $this->perPage));
        if (self::DEBUG) {
            var_dump(__METHOD__, $value);
        }

        return $value;
    }
}
