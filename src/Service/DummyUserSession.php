<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\API\UserSessionInterface;

class DummyUserSession implements UserSessionInterface
{
    public function __construct()
    {
    }

    public function getUserIdentifier(): ?string
    {
        return 'dummy-user';
    }

    public function getUserRoles(): array
    {
        return [];
    }

    public function setSessionToken(?array $jwt): void
    {
    }

    public function getSessionLoggingId(): string
    {
        return 'session-logging-id';
    }

    public function getSessionCacheKey(): string
    {
        return 'session-cache-key';
    }

    public function getSessionTTL(): int
    {
        return 0;
    }
}
