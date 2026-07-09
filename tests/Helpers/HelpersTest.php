<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Helpers;

use Dbp\Relay\CoreBundle\Helpers\MimeTools;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testMimeTools()
    {
        $this->assertSame(MimeTools::getDataURI('foobar', 'text/html'), 'data:text/html;base64,Zm9vYmFy');

        $this->assertSame(
            MimeTools::getMimeType(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12Mo3CQPAALaAUMu4mcOAAAAAElFTkSuQmCC', true)),
            'image/png');

        $this->assertSame(MimeTools::getFileExtensionForMimeType('image/png'), 'png');
    }

    public function testCreateAddressArray(): void
    {
        $this->assertEquals(['street' => 'Main St', 'postalCode' => '12345', 'city' => 'Anytown', 'country' => 'USA', 'additionalInformation' => 'first floor'],
            Tools::createAddressArray('Main St', '12345', 'Anytown', 'USA', 'first floor'));
        $this->assertEquals([],
            Tools::createAddressArray(null, null, null, null, null, false));
        $this->assertEquals(['street' => null, 'postalCode' => null, 'city' => null, 'country' => null, 'additionalInformation' => null],
            Tools::createAddressArray(null, null, null, null, null, true));
    }
}
