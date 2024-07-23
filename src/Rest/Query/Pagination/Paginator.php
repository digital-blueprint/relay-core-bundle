<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Pagination;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;

/**
 * @template T of object
 *
 * @implements PartialPaginatorInterface<T>
 * @implements \Iterator<T>
 */
abstract class Paginator implements \Iterator, PartialPaginatorInterface
{
    protected int $currentPosition = 0;
    protected array $items;
    protected int $maxNumItemsPerPage;
    protected int $currentPageNumber;

    protected function __construct(array $items, int $currentPageNumber, int $maxNumItemsPerPage)
    {
        if ($currentPageNumber < 1) {
            throw new ApiError(500, 'current page number must be greater than or equal to one');
        }
        if ($maxNumItemsPerPage <= 0) {
            if ($maxNumItemsPerPage === 0 && empty($items)) {
                $maxNumItemsPerPage = Pagination::MAX_NUM_ITEMS_PER_PAGE_DEFAULT;
            } else {
                throw new ApiError(500, 'maximum number of items per page must be greater than zero for non-empty results');
            }
        }

        $this->items = $items;
        $this->currentPageNumber = $currentPageNumber;
        $this->maxNumItemsPerPage = $maxNumItemsPerPage;

        $this->rewind();
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->items[$this->currentPosition];
    }

    public function next(): void
    {
        ++$this->currentPosition;
    }

    /**
     * @return bool|float|int|mixed|string|null
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->currentPosition;
    }

    public function valid(): bool
    {
        return
            $this->currentPosition < count($this->items)
            && $this->currentPosition < $this->maxNumItemsPerPage;
    }

    public function rewind(): void
    {
        $this->currentPosition = 0;
    }

    /**
     * Returns the current page number (page numbering starts at 1).
     */
    public function getCurrentPage(): float
    {
        return $this->currentPageNumber;
    }

    /**
     * Returns the maximum number of page items.
     */
    public function getItemsPerPage(): float
    {
        return $this->maxNumItemsPerPage;
    }

    /**
     * Returns the number of current page items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns the current page items.
     */
    public function getItems(): array
    {
        return iterator_to_array($this, false);
    }
}
