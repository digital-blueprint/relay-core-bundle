<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\API;

interface UserSessionProviderInterface
{
    /**
     * The unique identifier of the authenticated user. Or null in case it is called
     * before the user is known or if the user is a system.
     *
     * Can be derived from the session token for example.
     */
    public function getUserIdentifier(): ?string;

    /**
     * Returns an ID represents a "session" of a user which can be used for logging. It should not be possible to
     * figure out which user is behind the ID based on the ID itself and the ID should change regularly.
     * This is useful for connecting various requests together for logging while not exposing details about the user.
     *
     * Can be derived from long-running session IDs embedded in the token for example.
     *
     * Return null in case no logging ID exists
     */
    public function getSessionLoggingId(): ?string;

    /**
     * Returns a unique caching key that can be used to cache metadata related to the current user session like
     * any user metadata, authorization related information etc.
     * It should not be possible to figure out which user is behind the ID based on the ID itself and the ID should
     * change regularly (after a logout/login or a key refresh for example).
     *
     * For example a hashed version of the token.
     *
     * Return null in case no appropriate cache key exists.
     */
    public function getSessionCacheKey(): ?string;

    /**
     * Should return the duration the session is valid (as a whole, not from now) in seconds.
     * After the specified amount of time has passed the logging ID and the caching key should have changed.
     *
     * This is mostly useful for limiting the cache.
     *
     * For example the lifespan of the token.
     *
     * Return <0 in case that information isn't available.
     */
    public function getSessionTTL(): int;
}
