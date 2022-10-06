<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

interface AuthorizationUserInterface
{
    public function hasRole(string $roleName): bool;

    /**
     * @return mixed|null
     */
    public function getAttribute(string $attributeName);

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject): bool;
}
