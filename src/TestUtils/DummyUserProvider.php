<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use DBP\API\CoreBundle\Keycloak\KeycloakBearerUserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class DummyUserProvider implements KeycloakBearerUserProviderInterface
{
    private $user;

    public function __construct(KeycloakBearerUser $user)
    {
        $this->user = $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ($this->user->getAccessToken() !== $identifier) {
            throw new AuthenticationException('invalid token');
        }

        return $this->user;
    }
}
