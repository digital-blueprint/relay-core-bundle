<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Helpers;

use Dbp\Relay\CoreBundle\Helpers\GuzzleTools;
use Dbp\Relay\CoreBundle\Helpers\MimeTools;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class HelpersTest extends TestCase
{
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
