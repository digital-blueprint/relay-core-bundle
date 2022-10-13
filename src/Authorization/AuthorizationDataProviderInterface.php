<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

interface AuthorizationDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getAvailableAttributes(): array;

    public function getUserAttributes(string $userIdentifier): array;
}
