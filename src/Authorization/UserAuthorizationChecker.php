<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserAuthorizationChecker
{
    public const PRIVILEGE_NAME_ATTRIBUTE = 'name';
    public const PRIVILEGE_EXPRESSION_ATTRIBUTE = 'expression';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var array */
    private $privileges;

    /** @var UserExtender|null */
    private $userExtender;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->privileges = [];
        $this->userExtender = null;
    }

    public function addPrivileges(array $privileges)
    {
        foreach ($privileges as $privilege) {
            $this->privileges[$privilege[self::PRIVILEGE_NAME_ATTRIBUTE]] = $privilege[self::PRIVILEGE_EXPRESSION_ATTRIBUTE];
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject = null): bool
    {
        return $this->getUserExtender()->hasPrivilege($privilegeName, $subject);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasRole(string $roleName): bool
    {
        return $this->getCurrentUser()->hasRole($roleName);
    }

    /**
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getAttribute(string $attributeName)
    {
        return $this->getCurrentUser()->getAttribute($attributeName);
    }

    private function getUserExtender(): UserExtender
    {
        if ($this->userExtender === null) {
            $this->userExtender = new UserExtender($this->getCurrentUser(), $this->privileges);
        }

        return $this->userExtender;
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof UserInterface) {
            return $user;
        } else {
            throw new AuthorizationException('current user not available', AuthorizationException::USER_NOT_AVAILABLE);
        }
    }
}
