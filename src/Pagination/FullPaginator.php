<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

use ApiPlatform\Core\DataProvider\PaginatorInterface;

class FullPaginator extends Paginator implements PaginatorInterface
{
    /** @var int */
    private $totalNumItems;

    public function __construct(array $items, int $page, int $numItemsPerPage, int $totalNumItems)
    {
        parent::__construct($items, $page, $numItemsPerPage);

        $this->totalNumItems = $totalNumItems;
    }

    /**
     * Gets last page.
     */
    public function getLastPage(): float
    {
        return ceil($this->getTotalItems() / $this->getItemsPerPage());
    }

    /**
     * Gets the number of items in the whole collection.
     */
    public function getTotalItems(): float
    {
        return $this->totalNumItems;
    }
}
