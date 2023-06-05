<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\API;

interface UserSessionInterface
{
    /**
     * The unique identifier of the authenticated user. Or null in case it is called
     * before the user is known or if the user is a system.
     */
    public function getUserIdentifier(): ?string;

    /**
     * Indicates whether the user is authenticated.
     */
    public function isAuthenticated(): bool;

    /**
     * Returns an ID represents a "session" of a user which can be used for logging. It should not be possible to
     * figure out which user is behind the ID based on the ID itself and the ID should change regularly.
     * This is useful for connecting various requests together for logging while not exposing details about the user.
     */
    public function getSessionLoggingId(): string;

    /**
     * @deprecated
     */
    public function getUserRoles(): array;

    /**
     * Returns a unique caching key that can be used to cache metadata related to the current user session like
     * any user metadata, authorization related information etc.
     * It should not be possible to figure out which user is behind the ID based on the ID itself and the ID should
     * change regularly (after a logout/login or a key refresh for example).
     */
    public function getSessionCacheKey(): string;

    /**
     * Returns the duration the session is valid (as a whole, not from now) in seconds.
     * After the specified amount of time has passed the logging ID and the caching key should have changed.
     *
     * This is mostly useful for limiting the cache.
     */
    public function getSessionTTL(): int;
}
