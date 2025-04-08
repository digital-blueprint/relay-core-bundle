<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

class TestUserSession implements UserSessionInterface
{
    public function __construct(
        private ?string $userIdentifier = null,
        private array $symfonyRoles = [],
        private bool $isAuthenticated = false,
        private readonly bool $isServiceAccount = false)
    {
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->userIdentifier = $identifier;
    }

    public function setRoles(array $roles): void
    {
        $this->symfonyRoles = $roles;
    }

    public function setSessionToken(?array $jwt): void
    {
    }

    public function getUserRoles(): array
    {
        return $this->symfonyRoles;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->isAuthenticated && $this->isServiceAccount === false ? $this->userIdentifier : null;
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

    public function getSessionCacheTTL(): int
    {
        return 42;
    }

    public function getSessionTTL(): int
    {
        return $this->getSessionCacheTTL();
    }

    public function isServiceAccount(): bool
    {
        return $this->isServiceAccount;
    }
}
