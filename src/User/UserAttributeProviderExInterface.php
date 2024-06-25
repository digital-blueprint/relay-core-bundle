<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

/**
 * Extended version of UserAttributeProviderInterface which also allows retrieving single attributes.
 */
interface UserAttributeProviderExInterface extends UserAttributeProviderInterface
{
    /**
     * Returns the attribute value for the given $userIdentifier.
     *
     * In case $userIdentifier is null, the default values of the attribute should be returned.
     *
     * If the passed $name is not known it should throw UserAttributeException.
     *
     * @return array<string, mixed>
     *
     * @throws UserAttributeException
     */
    public function getUserAttribute(?string $userIdentifier, string $name): mixed;

    /**
     * Returns whether the given attribute name is available.
     */
    public function hasUserAttribute(string $name): bool;
}
