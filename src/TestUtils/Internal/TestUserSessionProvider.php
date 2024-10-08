<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils\Internal;

use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;

/**
 * @internal
 */
class TestUserSessionProvider implements UserSessionProviderInterface
{
    /** @var string|null */
    private $userIdentifier;

    public function __construct(?string $userIdentifier = null)
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
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
        return false;
    }
}
