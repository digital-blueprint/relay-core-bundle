<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\API;

/**
 * All methods except isAuthenticated() can only be used if the user is authenticated and isAuthenticated() returns true.
 */
interface UserSessionInterface
{
    /**
     * The unique identifier of the authenticated user. Or null if the user identifier is not known.
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
     * Returns a unique caching key that can be used to cache metadata related to the current user session like
     * any user metadata, authorization related information etc.
     * It should not be possible to figure out which user is behind the ID based on the ID itself and the ID should
     * change regularly (after a logout/login or a key refresh for example).
     */
    public function getSessionCacheKey(): string;

    /**
     * An amount of time in seconds after which the result of getSessionCacheKey() has changed, i.e. the old
     * cached data can be removed without performance impat. This is mostly useful for pruning caches created with
     * getSessionCacheKey().
     */
    public function getSessionCacheTTL(): int;

    /**
     * Returns whether the user is a service account, or a real user.
     */
    public function isServiceAccount(): bool;

    /**
     * @deprecated
     */
    public function getUserRoles(): array;

    /**
     * @deprecated use getSessionCacheTTL() instead
     */
    public function getSessionTTL(): int;
}
