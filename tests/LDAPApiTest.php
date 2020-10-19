<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use Adldap\Connections\ConnectionInterface;
use Adldap\Models\User as AdldapUser;
use Adldap\Query\Builder;
use Adldap\Query\Grammar;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Service\DBPLogger;
use DBP\API\CoreBundle\Service\LDAPApi;
use DBP\API\CoreBundle\Service\TUGOnlineApi;
use Mockery;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Security\Core\Security;

class LDAPApiTest extends ApiTestCase
{
    /**
     * @var LDAPApi
     */
    private $api;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();
        $security = new Security($client->getContainer());
        $nullLogger = new Logger('dummy', [new NullHandler()]);
        $logger = new DBPLogger($nullLogger, $security);
        $tugapi = new TUGOnlineApi($client->getContainer(), $logger);

        $this->api = new LDAPApi($client->getContainer(), $tugapi, $security, $nullLogger);
    }

    public function testBasic()
    {
        $this->expectExceptionMessageMatches('/.*/');
        $this->api->getPerson('____nope____');
    }

    protected function newBuilder()
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        return new Builder($connection, new Grammar());
    }

    public function testLDAPParsing()
    {
        $user = new AdldapUser([
            'cn' => ['John Doe'],
            'dateofbirth' => ['1994-06-24 00:00:00'],
        ], $this->newBuilder());

        $person = $this->api->personFromUserItem($user, false);

        $this->assertEquals(new \DateTime('1994-06-24'), $person->getBirthDate());
    }
}
