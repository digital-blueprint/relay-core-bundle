<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Pagination;

class PartialPaginator extends Paginator
{
    public function __construct(array $items, int $page, int $numItemsPerPage)
    {
        parent::__construct($items, $page, $numItemsPerPage);
    }
}
