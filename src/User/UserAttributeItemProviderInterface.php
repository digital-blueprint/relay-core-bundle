<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

/**
 * Extended version of UserAttributeProviderInterface which also allows retrieving single attributes.
 */
interface UserAttributeItemProviderInterface extends UserAttributeProviderInterface
{
    /**
     * Should return the user attribute value for the user with the given $userIdentifier.
     *
     * In case $userIdentifier is null, the default values of the attribute should be returned.
     *
     * If the user attribute with the given $name is not defined for this provider,
     * i.e., hasUserAttribute($name) returns false, it should throw UserAttributeException.
     *
     * @return array<string, mixed>
     *
     * @throws UserAttributeException
     */
    public function getUserAttribute(?string $userIdentifier, string $name): mixed;

    /**
     * Should return true if the given user attribute $name is defined for this provider,
     * i.e., getUserAttribute will return a value for this attribute, false otherwise.
     */
    public function hasUserAttribute(string $name): bool;
}
