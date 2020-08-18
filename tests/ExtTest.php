<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProvider;
use DBP\API\CoreBundle\TestUtils\DummyPersonProvider;
use DBP\API\CoreBundle\TestUtils\DummyUserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class ExtTest extends ApiTestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function withUserEx($id, $token, $roles, $functions, $scopes, $isServiceAccount): UserInterface
    {
        $container = $this->client->getContainer();

        if ($isServiceAccount) {
            $person = null;
            $id = null;
        } else {
            $person = new Person();
            $person->setIdentifier($id);
            $person->setFunctions($functions);
            $person->setRoles($roles);
            $person->setAccountTypes([]);
        }
        $personProvider = new DummyPersonProvider($person);

        $user = new KeycloakBearerUser($id, $token, $personProvider, $scopes);
        $userProvider = new DummyUserProvider($user);
        $container->set("test.App\Security\User\KeycloakBearerUserProvider", $userProvider);
        $container->set("test.App\Service\PersonProviderInterface", $personProvider);

        return $user;
    }

    protected function withUser($id, $token, $roles, $functions, $scopes): UserInterface
    {
        return $this->withUserEx($id, $token, $roles, $functions, $scopes, false);
    }

    protected function withServiceAccount($id, $token, $scopes): UserInterface
    {
        return $this->withUserEx($id, $token, [], [], $scopes, true);
    }

    public function testServiceAccountRoles()
    {
        $user = $this->withServiceAccount('foobar', '42', ['SCOPE1', 'SCOPE2']);
        $this->assertEquals(['ROLE_SCOPE_SCOPE1', 'ROLE_SCOPE_SCOPE2'], $user->getRoles());
    }

    public function testScopeToRolesMapping()
    {
        $this->withUser('foobar', '42', ['SOMEROLE'], ['FUNC'], ['SCOPE1', 'SCOPE2']);

        $container = $this->client->getContainer();
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
        $this->withUser('foobar', '42', [], [], []);
        $response = $this->client->request('GET', '/people/foobar');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetPersonWrongAuth()
    {
        $this->withUser('foobar', '42', [], [], []);
        $response = $this->client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer NOT42',
        ]]);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testGetPerson()
    {
        $this->withUser('foobar', '42', [], [], []);
        $response = $this->client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer 42',
        ]]);
        $this->assertJson($response->getContent(false));
        $data = json_decode($response->getContent(false), true);
        $this->assertEquals('/people/foobar', $data['@id']);
    }

    public function testGetPersonRolesFunctions()
    {
        $this->withUser('foobar', '42', ['ROLE'], ['FUNC'], []);
        $response = $this->client->request('GET', '/people/foobar', ['headers' => [
            'Authorization' => 'Bearer 42',
        ]]);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['ROLE'], $data['roles']);
        $this->assertEquals(['FUNC'], $data['functions']);
    }
}
