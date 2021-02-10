<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Service\CampusOnline\OrganizationApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

class OrganizationTest extends ApiTestCase
{
    private $api;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();
        $nullLogger = new Logger('dummy', [new NullHandler()]);
        $container = $client->getContainer();
        $this->api = new OrganizationApi($container, $nullLogger);
        $this->api->setApiKey('nope');
        $this->api->enableCache(false);
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function testGetOrganizationById()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'text/xml;charset=utf-8'], file_get_contents(__DIR__.'/co_orgunit_response.xml')),
        ]);
        $org = $this->api->getOrganizationById('2322-F4370', 'en');
        $this->assertSame('2322-F4370', $org->getIdentifier());
        $this->assertSame('Institute of Fundamentals and Theory in Electrical  Engineering', $org->getName());
        $this->assertSame('F4370', $org->getAlternateName());
        $this->assertSame('https://online.tugraz.at/tug_online/wborg.display?pOrgNr=2322', $org->getUrl());
    }

    public function testGetOrganizationByIdNoPermission()
    {
        $this->mockResponses([
            new Response(403, [], 'error'),
        ]);
        $this->expectException(ItemNotLoadedException::class);
        $this->api->getOrganizationById('2234-F1234', 'en');
    }

    public function testGetOrganizationsByPersonEmpty()
    {
        $person = new Person();
        $person->setExtraData('tug-functions', []);
        $result = $this->api->getOrganizationsByPerson($person, 'library-manager', 'en');
        $this->assertSame([], $result);
    }

    public function testGetOrganizationsByPerson()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'text/xml;charset=utf-8'], file_get_contents(__DIR__.'/co_orgunit_response.xml')),
        ]);
        $person = new Person();
        $person->setExtraData('tug-functions', ['F_BIB:F:4370:2322']);
        $result = $this->api->getOrganizationsByPerson($person, 'library-manager', 'en');
        $this->assertCount(1, $result);
        $this->assertSame('2322-F4370', $result[0]->getIdentifier());
    }

    public function testGetOrganizationsByPersonNested()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'text/xml;charset=utf-8'], file_get_contents(__DIR__.'/co_orgunit_response_nested.xml')),
        ]);
        $person = new Person();
        $person->setExtraData('tug-functions', ['F_BIB:F:6350:2391']);
        $result = $this->api->getOrganizationsByPerson($person, 'library-manager', 'en');
        $this->assertCount(1, $result);
        $this->assertSame('2391-F6350', $result[0]->getIdentifier());
    }

    public function testSpecialOrg()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'text/xml;charset=utf-8'], file_get_contents(__DIR__.'/co_orgunit_response_nested.xml')),
        ]);
        $person = new Person();
        $person->setExtraData('tug-functions', ['F_BIB:F:2135:1226_1231']);
        $result = $this->api->getOrganizationsByPerson($person, 'library-manager', 'en');
        $this->assertCount(1, $result);
        $this->assertSame('1226_1231-F2135', $result[0]->getIdentifier());
    }
}
