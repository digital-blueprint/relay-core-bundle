<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\API;

interface UserInterface
{
    public function hasRole(string $roleName): bool;

    /**
     * @return mixed|null
     */
    public function getAttribute(string $attributeName);
}
