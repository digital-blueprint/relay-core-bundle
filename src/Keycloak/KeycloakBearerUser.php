<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakBearerUser implements UserInterface
{
    /**
     * @var string[]
     */
    private $roles;

    /**
     * @var string|null
     */
    private $identifier;

    public function __construct(?string $identifier, array $roles)
    {
        $this->roles = $roles;
        $this->identifier = $identifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier ?? '';
    }

    public function eraseCredentials()
    {
    }
}
