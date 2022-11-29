<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

interface AuthorizationDataProviderInterface
{
    /**
     * Returns a list of available attributes.
     *
     * @return string[]
     */
    public function getAvailableAttributes(): array;

    /**
     * Returns an array of attribute names and values.
     * In case an attribute has no value then it isn't contained.
     *
     * In case $userIdentifier is null then there is no active user and
     * only globally available attributes shold be returned.
     *
     * @return array<string, mixed>
     */
    public function getUserAttributes(?string $userIdentifier): array;
}
