<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Auth;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * This service provides user session information, either sourcing information from the active auth provider
 * or in case it is used from the CLI or unauthenticated then it returns some reasonable defaults.
 */
class UserSession implements UserSessionInterface
{
    /**
     * @var ?UserSessionProviderInterface
     */
    private $provider;

    /**
     * @var Security
     */
    private $security;

    public function __construct(?Security $security = null)
    {
        $this->security = $security;
    }

    private function ensureProvider(): UserSessionProviderInterface
    {
        if ($this->provider === null) {
            throw new \RuntimeException('Can only be called if authenticated');
        }

        return $this->provider;
    }

    public function setProvider(?UserSessionProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getUserIdentifier(): ?string
    {
        $provider = $this->ensureProvider();

        return $provider->getUserIdentifier();
    }

    public function isAuthenticated(): bool
    {
        return $this->provider !== null /* this criterion alone would suffice */
            && $this->security !== null
            && $this->security->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function getSessionLoggingId(): string
    {
        $provider = $this->ensureProvider();
        $id = $provider->getSessionLoggingId();
        if ($id === null) {
            $id = 'unknown';
        }

        return $id;
    }

    public function getSessionCacheKey(): string
    {
        $provider = $this->ensureProvider();

        return $provider->getSessionCacheKey();
    }

    public function getSessionCacheTTL(): int
    {
        $provider = $this->ensureProvider();
        $ttl = $provider->getSessionCacheTTL();
        if ($ttl === -1) {
            $ttl = 60;
        }

        return $ttl;
    }

    public function getSessionTTL(): int
    {
        return $this->getSessionCacheTTL();
    }

    public function getUserRoles(): array
    {
        $this->ensureProvider();
        $user = $this->security->getUser();
        if ($user === null) {
            return [];
        }

        return $user->getRoles();
    }

    public function isServiceAccount(): bool
    {
        $provider = $this->ensureProvider();

        return $provider->isServiceAccount();
    }
}
