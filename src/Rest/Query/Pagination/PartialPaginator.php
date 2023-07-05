<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Pagination;

/**
 * Paginator that holds the items of one page and is NOT aware of the total amount of result items.
 */
class PartialPaginator extends Paginator
{
    public function __construct(array $items, int $page, int $numItemsPerPage)
    {
        parent::__construct($items, $page, $numItemsPerPage);
    }
}
