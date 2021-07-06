<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;

trait UserAuthTrait
{
    public function withUser(?string $id, string $token, array $options = []): array
    {
        $client = ApiTestCase::createClient();
        $roles = $options['roles'] ?? [];
        $user = new KeycloakBearerUser($id, $roles);
        $userProvider = new DummyUserProvider($user, $token);
        $container = $client->getContainer();
        $container->set('test.UserProviderInterface', $userProvider);

        return [$client, $user];
    }
}
