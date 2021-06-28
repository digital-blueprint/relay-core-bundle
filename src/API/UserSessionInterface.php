<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\API;

interface UserSessionInterface
{
    /**
     * This gets called with the active JWT before any of the other methods are called.
     */
    public function setSessionToken(?array $jwt): void;

    /**
     * The unique identifier of the authenticated user. Or null in case the user is a system.
     *
     * Can be derived from the session token for example.
     */
    public function getUserIdentifier(): ?string;

    /**
     * Returns a list of Symfony user roles, like ['ROLE_FOOBAR'].
     *
     * Can be derived from the session token for example.
     */
    public function getUserRoles(): array;

    /**
     * Returns an ID represents a "session" of a user which can be used for logging. It should not be possible to
     * figure out which user is behind the ID based on the ID itself and the ID should change regularly.
     * This is useful for connecting various requests together for logging while not exposing details about the user.
     *
     * Can be derived from long running session IDs embedded in the token for example.
     */
    public function getSessionLoggingId(): string;

    /**
     * Returns a unique caching key that can be used to cache metadata related to the current user session like
     * any user metadata, authorization related information etc.
     * It should not be possible to figure out which user is behind the ID based on the ID itself and the ID should
     * change regularly (after a logout/login or a key refresh for example).
     *
     * For example a hashed version of the token.
     */
    public function getSessionCacheKey(): string;

    /**
     * Should return the duration the session is valid (as a whole, not from now) in seconds.
     * After the specified amount of time has passed the logging ID and the caching key should have changed.
     *
     * This is mostly useful for limiting the cache.
     *
     * For example the lifespan of the token.
     */
    public function getSessionTTL(): int;
}
