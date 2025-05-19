<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query;

use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
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

    public function testDeprecatedGetAllResults(): void
    {
        $lastItem = 100;
        $getPageStartIndexBased = function (int $currentPageStartIndex, int $maxNumItemsPerPage) use ($lastItem): iterable {
            foreach (range($currentPageStartIndex, $currentPageStartIndex + $maxNumItemsPerPage - 1) as $i) {
                if ($i > $lastItem) {
                    break;
                }
                yield $i;
            }
        };

        $expected = 0;
        $currentResultItem = null;

        foreach (Pagination::getAllResults($getPageStartIndexBased, 32) as $resultItem) {
            $currentResultItem = $resultItem;
            $this->assertEquals($expected++, $currentResultItem);
        }
        $this->assertEquals($currentResultItem, $lastItem);
    }

    public function testGetAllResultsPageStartIndexBased(): void
    {
        $currentResultItem = null;
        $lastItem = 100;

        $getPageStartIndexBased = function (int $currentPageStartIndex, int $maxNumItemsPerPage) use ($lastItem): iterable {
            foreach (range($currentPageStartIndex, $currentPageStartIndex + $maxNumItemsPerPage - 1) as $i) {
                if ($i > $lastItem) {
                    break;
                }
                yield $i;
            }
        };

        $expected = 0;
        foreach (Pagination::getAllResultsPageStartIndexBased($getPageStartIndexBased, 32) as $resultItem) {
            $currentResultItem = $resultItem;
            $this->assertEquals($expected++, $currentResultItem);
        }
        $this->assertEquals($currentResultItem, $lastItem);

        $getPageNumberBasedArray = function (int $currentPageStartIndex, int $maxNumItemsPerPage) use ($lastItem): iterable {
            return range($currentPageStartIndex, min($lastItem, $currentPageStartIndex + $maxNumItemsPerPage - 1));
        };

        $expected = 0;
        foreach (Pagination::getAllResultsPageStartIndexBased($getPageNumberBasedArray, 32) as $resultItem) {
            $currentResultItem = $resultItem;
            $this->assertEquals($expected++, $currentResultItem);
        }
        $this->assertEquals($currentResultItem, $lastItem);
    }

    public function testGetAllResultsPageNumberBased(): void
    {
        $currentResultItem = null;
        $lastItem = 100;

        $getPageNumberBased = function (int $currentPageNumber, int $maxNumItemsPerPage) use ($lastItem): iterable {
            $currentPageStartIndex = $maxNumItemsPerPage * ($currentPageNumber - 1);
            foreach (range($currentPageStartIndex, $currentPageStartIndex + $maxNumItemsPerPage - 1) as $i) {
                if ($i > $lastItem) {
                    break;
                }
                yield $i;
            }
        };

        $expected = 0;
        foreach (Pagination::getAllResultsPageNumberBased($getPageNumberBased, 32) as $resultItem) {
            $currentResultItem = $resultItem;
            $this->assertEquals($expected++, $currentResultItem);
        }
        $this->assertEquals($currentResultItem, $lastItem);

        $getPageNumberBasedArray = function (int $currentPageNumber, int $maxNumItemsPerPage) use ($lastItem): iterable {
            $currentPageStartIndex = $maxNumItemsPerPage * ($currentPageNumber - 1);

            return range($currentPageStartIndex, min($lastItem, $currentPageStartIndex + $maxNumItemsPerPage - 1));
        };

        $expected = 0;
        foreach (Pagination::getAllResultsPageNumberBased($getPageNumberBasedArray, 32) as $resultItem) {
            $currentResultItem = $resultItem;
            $this->assertEquals($expected++, $currentResultItem);
        }
        $this->assertEquals($currentResultItem, $lastItem);
    }
}
