<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use DBP\API\CoreBundle\TestUtils\UserAuthTrait;
use Symfony\Component\HttpFoundation\Response;

class ExtTest extends ApiTestCase
{
    use UserAuthTrait;

    public function testServiceAccountRoles()
    {
        [$client, $user] = $this->withUser(null, '42', ['scopes' => ['SCOPE1', 'SCOPE2']]);
        $this->assertEquals(['ROLE_SCOPE_SCOPE1', 'ROLE_SCOPE_SCOPE2'], $user->getRoles());
    }

    public function testScopeToRolesMapping()
    {
        [$client, $user] = $this->withUser('foobar', '42', [
            'roles' => ['SOMEROLE'],
            'functions' => ['FUNC'],
            'scopes' => ['SCOPE1', 'SCOPE2'],
        ]);

        $container = $client->getContainer();
        $userProvider = $container->get("test.App\Security\User\KeycloakBearerUserProvider");
        $user = $userProvider->loadUserByUsername('42');
        $this->assertEquals($user->getRoles(), ['SOMEROLE', 'ROLE_SCOPE_SCOPE1', 'ROLE_SCOPE_SCOPE2']);
    }

    public function testIsServiceAccountToken()
    {
        $this->assertTrue(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'openid foo bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'openid']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo openid bar']));
        $this->assertFalse(KeycloakBearerUserProvider::isServiceAccountToken(['scope' => 'foo bar openid']));
    }

    public function testGetPersonNoAuth()
    {
        [$client, $user] = $this->withUser('foobar', '42');
        $response = $client->request('GET', '/people/foobar');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetPersonWrongAuth()
    {
        [$client, $user] = $this->withUser('foobar', '42');
        $response = $client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer NOT42',
        ]]);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testGetPerson()
    {
        [$client, $user] = $this->withUser('foobar', '42');
        $response = $client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer 42',
        ]]);
        $this->assertJson($response->getContent(false));
        $data = json_decode($response->getContent(false), true);
        $this->assertEquals('/people/foobar', $data['@id']);
    }

    public function testGetPersonRolesFunctions()
    {
        [$client, $user] = $this->withUser('foobar', '42', ['roles' => ['ROLE'], 'functions' => ['FUNC']]);
        $response = $client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer 42',
        ]]);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['ROLE'], $data['roles']);
        $this->assertEquals(['FUNC'], $data['functions']);
    }
}
