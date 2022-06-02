<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

class AllItemsPaginator extends FullPaginator
{
    public function __construct(array $items, int $currentPageNumber, int $maxNumItemsPerPage)
    {
        parent::__construct($items, $currentPageNumber, $maxNumItemsPerPage, count($items));
    }

    public function valid(): bool
    {
        return
            ($this->currentPosition < $this->totalNumItems) &&
            ($this->currentPosition < ($this->currentPageNumber * $this->maxNumItemsPerPage)) &&
            ($this->currentPosition >= (($this->currentPageNumber - 1) * $this->maxNumItemsPerPage));
    }

    public function rewind(): void
    {
        $this->currentPosition = ($this->currentPageNumber - 1) * $this->maxNumItemsPerPage;
    }

    public function count(): int
    {
        return $this->currentPageNumber < $this->getLastPage() ?
            $this->maxNumItemsPerPage :
            (($this->currentPageNumber - 1) * $this->maxNumItemsPerPage) - $this->totalNumItems;
    }
}
