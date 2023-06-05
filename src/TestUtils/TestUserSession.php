<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;

class TestUserSession implements UserSessionInterface
{
    /** @var TestUserSessionProvider */
    private $userSessionProvider;

    /** @var array */
    private $roles;

    public function __construct(string $id = null, array $roles = [])
    {
        $this->userSessionProvider = new TestUserSessionProvider($id);
        $this->roles = $roles;
    }

    public function setIdentifier(string $identifier)
    {
        $this->userSessionProvider->setUserIdentifier($identifier);
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
        return $this->userSessionProvider->getUserIdentifier();
    }

    public function isAuthenticated(): bool
    {
        return $this->userSessionProvider->getUserIdentifier() !== null;
    }

    public function getSessionLoggingId(): string
    {
        return $this->userSessionProvider->getSessionLoggingId();
    }

    public function getSessionCacheKey(): string
    {
        return $this->userSessionProvider->getSessionCacheKey();
    }

    public function getSessionTTL(): int
    {
        return $this->userSessionProvider->getSessionTTL();
    }
}
