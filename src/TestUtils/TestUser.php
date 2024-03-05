<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private $id;
    private $roles;

    public function __construct(string $id, array $roles = [])
    {
        $this->id = $id;
        $this->roles = $roles;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }
}
