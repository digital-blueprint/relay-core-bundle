<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;

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

    public function getSessionTTL(): int
    {
        return 42;
    }
}
