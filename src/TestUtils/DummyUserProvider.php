<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use DBP\API\CoreBundle\Keycloak\KeycloakBearerUser;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DummyUserProvider implements UserProviderInterface
{
    /* @var KeycloakBearerUser */
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function loadUserByUsername($username)
    {
        $token = $this->user->getAccessToken();
        if ($token !== $username) {
            throw new BadCredentialsException('invalid token');
        }

        return $this->user;
    }

    public function refreshUser(UserInterface $user)
    {
        if ($user !== $this->user) {
            throw new UnsupportedUserException();
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return true;
    }
}
