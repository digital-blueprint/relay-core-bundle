<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private $id;
    private $roles;

    public function __construct(string $id = 'id', array $roles = [])
    {
        $this->id = $id;
        $this->roles = $roles;
    }

    public function getRoles()
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

    public function eraseCredentials()
    {
    }

    public function getUsername()
    {
        return $this->id;
    }

    public function getUserIdentifier()
    {
        return $this->id;
    }
}
