<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;

trait UserAuthTrait
{
    public function withUser(?string $id, string $token, array $options = []): array
    {
        $client = ApiTestCase::createClient();
        $roles = $options['roles'] ?? [];
        $scopes = $options['scopes'] ?? [];
        $person = $options['person'] ?? new Person();

        if ($id === null) {
            $person = null;
        } else {
            $person->setIdentifier($id);
            $person->setRoles($roles);
        }
        $personProvider = new DummyPersonProvider($person);
        $user = new KeycloakBearerUser($id, $token, $personProvider, $scopes);
        $userProvider = new DummyUserProvider($user);

        $container = $client->getContainer();
        $container->set('test.UserProviderInterface', $userProvider);
        $container->set('test.PersonProviderInterface', $personProvider);

        return [$client, $user];
    }
}
