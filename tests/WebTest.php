<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use DBP\API\CoreBundle\TestUtils\UserAuthWebTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WebTest extends WebTestCase
{
    use UserAuthWebTrait;

    public function testUserLogin()
    {
        $client = $this->withUser('foo', ['bar']);
        $user = $this->getUser($client);
        $this->assertSame($user->getRoles(), ['bar']);
        $this->assertSame($user->getUserIdentifier(), 'foo');
    }

    public function testRequest()
    {
        $client = $this->withUser('foo', ['bar']);
        $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
