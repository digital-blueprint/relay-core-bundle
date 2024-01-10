<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Auth;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Uid\Uuid;

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

    public function __construct(Security $security = null)
    {
        $this->security = $security;
    }

    public function setProvider(?UserSessionProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function getUserIdentifier(): ?string
    {
        if ($this->provider === null) {
            return null;
        }

        return $this->provider->getUserIdentifier();
    }

    public function isAuthenticated(): bool
    {
        return $this->provider !== null /* this criterion alone would suffice */
            && $this->security !== null
            && $this->security->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function getSessionLoggingId(): string
    {
        $id = null;
        if ($this->provider !== null) {
            $id = $this->provider->getSessionLoggingId();
        }
        if ($id === null) {
            $id = 'unknown';
        }

        return $id;
    }

    public function getSessionCacheKey(): string
    {
        $key = null;
        if ($this->provider !== null) {
            $key = $this->provider->getSessionCacheKey();
        }
        if ($key === null) {
            $key = Uuid::v4()->toRfc4122();
        }

        return $key;
    }

    public function getSessionTTL(): int
    {
        $ttl = -1;
        if ($this->provider !== null) {
            $ttl = $this->provider->getSessionTTL();
        }
        if ($ttl === -1) {
            $ttl = 60;
        }

        return $ttl;
    }

    public function getUserRoles(): array
    {
        if ($this->provider === null) {
            return [];
        }
        $user = $this->security->getUser();
        if ($user === null) {
            return [];
        }

        return $user->getRoles();
    }
}
