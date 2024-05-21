<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

class TestUserSession implements UserSessionInterface
{
    private ?string $userIdentifier;

    private array $roles;

    private bool $isAuthenticated = false;

    public function __construct(?string $identifier = null, array $roles = [], bool $isAuthenticated = false)
    {
        $this->userIdentifier = $identifier;
        $this->roles = $roles;
        $this->isAuthenticated = $isAuthenticated;
    }

    public function setIdentifier(?string $identifier)
    {
        $this->userIdentifier = $identifier;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function setSessionToken(?array $jwt): void
    {
    }

    public function getUserRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }

    public function setIsAuthenticated(bool $isAuthenticated): void
    {
        $this->isAuthenticated = $isAuthenticated;
    }

    public function getSessionLoggingId(): string
    {
        return 'logging-id';
    }

    public function getSessionCacheKey(): string
    {
        return 'cache';
    }

    public function getSessionTTL(): int
    {
        return 42;
    }
}
