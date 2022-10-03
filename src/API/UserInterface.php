<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\API;

use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

interface UserInterface extends SymfonyUserInterface
{
    public function hasRole(string $roleName): bool;

    /**
     * @return mixed|null
     */
    public function getAttribute(string $attributeName);
}
