<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use DBP\API\CoreBundle\API\UserSessionInterface;

class TestUserSession implements UserSessionInterface
{
    private $id;
    private $roles;

    public function __construct(?string $id = 'id', array $roles = [])
    {
        $this->id = $id;
        $this->roles = $roles;
    }

    public function setIdentifier(?string $identifier)
    {
        $this->id = $identifier;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function setSessionToken(?array $jwt): void
    {
    }

    public function getUserIdentifier(): ?string
    {
        return $this->id;
    }

    public function getUserRoles(): array
    {
        return $this->roles;
    }

    public function getSessionLoggingId(): ?string
    {
        return 'logging-id';
    }

    public function getSessionCacheKey(): ?string
    {
        return 'cache';
    }

    public function getSessionTTL(): int
    {
        return 42;
    }
}
