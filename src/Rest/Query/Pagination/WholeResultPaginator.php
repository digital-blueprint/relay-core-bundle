<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Pagination;

/**
 * Paginator that holds the whole set of result items.
 * Note that it is a full paginator because partial pagination makes no sense with the whole result already at hand.
 */
class WholeResultPaginator extends PartialPaginator
{
    public function __construct(array $items, int $currentPageNumber, int $maxNumItemsPerPage)
    {
        parent::__construct($items, $currentPageNumber, $maxNumItemsPerPage);
    }

    public function valid(): bool
    {
        return
            ($this->currentPosition < count($this->items)) &&
            ($this->currentPosition < ($this->currentPageNumber * $this->maxNumItemsPerPage)) &&
            ($this->currentPosition >= (($this->currentPageNumber - 1) * $this->maxNumItemsPerPage));
    }

    public function rewind(): void
    {
        $this->currentPosition = ($this->currentPageNumber - 1) * $this->maxNumItemsPerPage;
    }

    public function count(): int
    {
        return $this->currentPageNumber < ceil(count($this->items) / $this->maxNumItemsPerPage) ?
            $this->maxNumItemsPerPage :
            count($this->items) - (($this->currentPageNumber - 1) * $this->maxNumItemsPerPage);
    }
}
