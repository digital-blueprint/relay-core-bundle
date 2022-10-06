<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

interface AuthorizationDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getAvailableRoles(): array;

    /**
     * @return string[]
     */
    public function getAvailableAttributes(): array;

    /**
     * @param string[] $userRoles
     * @param mixed[]  $userAttributes
     */
    public function getUserData(string $userId, array &$userRoles, array &$userAttributes): void;
}
