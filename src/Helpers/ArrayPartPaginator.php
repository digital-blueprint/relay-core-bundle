<?php
/**
 * This is a paginator for collection data providers to work with items from an array,
 * that only contains a part of the result set.
 */

namespace DBP\API\CoreBundle\Helpers;

class ArrayPartPaginator extends ArrayPaginator
{
    const DEBUG = false;
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
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->totalItemCount);
        }

        return $this->totalItemCount;
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

    public function rewind()
    {
        $this->position = 0;
        if (self::DEBUG) {
            var_dump(__METHOD__, $this->position);
        }
    }

    public function valid()
    {
        $value = isset($this->array[$this->position]);
        if (self::DEBUG) {
            var_dump(__METHOD__, $value);
        }

        return $value;
    }
}
