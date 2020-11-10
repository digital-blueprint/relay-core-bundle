<?php

declare(strict_types=1);

use DBP\API\CoreBundle\Service\LoggingProcessor;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Security;

class LoggingProcessorTest extends WebTestCase
{
    public function testFilter()
    {
        $client = static::createClient();
        $sec = new Security($client->getContainer());
        $processor = new LoggingProcessor($sec);

        $record = ['message' => 'http://foo.bar?token=secret'];
        $record = $processor->__invoke($record);
        $this->assertSame(['message' => 'http://foo.bar?token=hidden'], $record);
    }
}
