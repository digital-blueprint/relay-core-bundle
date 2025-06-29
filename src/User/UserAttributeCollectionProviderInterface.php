<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

interface UserAttributeCollectionProviderInterface extends UserAttributeProviderInterface
{
    /**
     * Returns the set of available user attribute names,
     * i.e., the names of all user attributes that this provider may provide.
     *
     * @return string[]
     */
    public function getAvailableAttributes(): array;

    /**
     * Returns an associative array of user attribute names (key) and values (value) for the
     * given $userIdentifier. The set of keys must match the set of available attribute names.
     *
     * In case $userIdentifier is null, the default values for all available attributes should be returned.
     *
     * @return array<string, mixed>
     */
    public function getUserAttributes(?string $userIdentifier): array;
}
