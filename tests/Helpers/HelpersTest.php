<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Helpers;

use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\CoreBundle\Helpers\ArrayPartPaginator;
use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use Dbp\Relay\CoreBundle\Helpers\MimeTools;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class HelpersTest extends TestCase
{
    public function testArrayFullPaginator()
    {
        $paginator = new ArrayFullPaginator([4, 5, 6], 1, 2);
        $this->assertSame($paginator->getCurrentPage(), 1.0);
        $this->assertSame($paginator->getItemsPerPage(), 2.0);
        $this->assertSame($paginator->getTotalItems(), 3.0);
        $this->assertSame($paginator->current(), 4);
        $this->assertSame($paginator->key(), 0);
        $this->assertTrue($paginator->valid());
        $paginator->next();
        $this->assertSame($paginator->current(), 5);
        $this->assertSame($paginator->getLastPage(), 2.0);
    }

    public function testArrayPartPaginator()
    {
        $paginator = new ArrayPartPaginator([4, 5, 6], 3, 1, 2);
        $this->assertSame($paginator->getCurrentPage(), 1.0);
        $this->assertSame($paginator->getItemsPerPage(), 2.0);
        $this->assertSame($paginator->getTotalItems(), 3.0);
        $this->assertSame($paginator->current(), 4);
        $this->assertSame($paginator->key(), 0);
        $this->assertTrue($paginator->valid());
        $paginator->next();
        $this->assertSame($paginator->current(), 5);
        $this->assertSame($paginator->getLastPage(), 2.0);
    }

    public function testGuzzleTools()
    {
        $middleware = GuzzleTools::createLoggerMiddleware(new NullLogger());
        $this->assertNotNull($middleware);
    }

    public function testMimeTools()
    {
        $this->assertSame(MimeTools::getDataURI('foobar', 'text/html'), 'data:text/html;base64,Zm9vYmFy');

        $this->assertSame(
            MimeTools::getMimeType(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12Mo3CQPAALaAUMu4mcOAAAAAElFTkSuQmCC', true)),
'image/png');

        $this->assertSame(MimeTools::getFileExtensionForMimeType('image/png'), 'png');
    }
}
