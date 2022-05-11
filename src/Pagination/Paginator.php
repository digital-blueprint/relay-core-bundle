<?php

declare(strict_types=1);
/**
 * This is a paginator for collection data providers to work with items from an array,
 * that only contains a part of the result set.
 */

namespace Dbp\Relay\CoreBundle\Pagination;

use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use Iterator;

abstract class Paginator implements Iterator, PartialPaginatorInterface
{
    protected $position;
    protected $items;
    protected $numItemsPerPage;
    protected $page;

    protected function __construct(array $items, int $page, int $numItemsPerPage)
    {
        $this->position = 0;
        $this->items = $items;
        $this->page = $page;
        $this->numItemsPerPage = $numItemsPerPage;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @return float|int|null
     */
    public function key()
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function getCurrentPage(): float
    {
        return $this->page;
    }

    public function getItemsPerPage(): float
    {
        return $this->numItemsPerPage;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
