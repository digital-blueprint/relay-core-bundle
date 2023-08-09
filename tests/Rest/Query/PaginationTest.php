<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query;

use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testPartial()
    {
        $paginator = new PartialPaginator([1, 2, 3], 2, 3);
        $this->assertSame(3, $paginator->count());
        $this->assertSame(1, $paginator->current());
        $this->assertSame([1, 2, 3], $paginator->getItems());
        $this->assertSame(2.0, $paginator->getCurrentPage());
        $this->assertSame(3.0, $paginator->getItemsPerPage());
        $this->assertSame([1, 2, 3], iterator_to_array($paginator));
        $paginator->next();
        $this->assertFalse($paginator->valid());
        $paginator->rewind();
        $this->assertTrue($paginator->valid());
    }

    public function testWhole()
    {
        $paginator = new WholeResultPaginator([1, 2, 3, 4, 5, 6, 7, 8, 9], 2, 3);
        $this->assertSame(3, $paginator->count());
        $this->assertSame(4, $paginator->current());
        $this->assertSame([4, 5, 6], $paginator->getItems());
        $this->assertSame(2.0, $paginator->getCurrentPage());
        $this->assertSame(3.0, $paginator->getItemsPerPage());
        $this->assertSame([3, 4, 5], array_keys(iterator_to_array($paginator)));
        $this->assertSame([4, 5, 6], array_values(iterator_to_array($paginator)));
        $paginator->next();
        $this->assertFalse($paginator->valid());
        $paginator->rewind();
        $this->assertTrue($paginator->valid());
    }
}
