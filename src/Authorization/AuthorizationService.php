<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationService
{
    public const ROLES_CONFIG_ATTRIBUTE = 'roles';
    public const PRIVILEGES_CONFIG_ATTRIBUTE = 'privileges';
    public const ATTRIBUTES_CONFIG_ATTRIBUTE = 'attributes';
    public const NAME_CONFIG_ATTRIBUTE = 'name';
    public const EXPRESSION_CONFIG_ATTRIBUTE = 'expression';

    /** @var UserAuthorizationChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser|null */
    private $currentAuthorizationUser;

    public function __construct(UserAuthorizationChecker $userAuthorizationChecker)
    {
        $this->userAuthorizationChecker = $userAuthorizationChecker;
        $this->currentAuthorizationUser = new AuthorizationUser($this->userAuthorizationChecker);
    }

    public function setAuthorizationConfig(array $config)
    {
        $this->userAuthorizationChecker->setConfig($config);
    }

    /**
     * @throws ApiError
     */
    public function denyAccessUnlessHasRole(string $roleName): void
    {
        if ($this->hasRoleInternal($roleName) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing role '.$roleName);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws ApiError
     */
    public function denyAccessUnlessHasPrivilege(string $privilegeName, $subject = null): void
    {
        if ($this->hasPrivilegeInternal($privilegeName, $subject) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing privilege '.$privilegeName);
        }
    }

    public function hasRole(string $roleName): bool
    {
        return $this->hasRoleInternal($roleName);
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->getAttributeInternal($attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject): bool
    {
        return $this->hasPrivilegeInternal($privilegeName, $subject);
    }

    private function hasRoleInternal(string $roleName): bool
    {
        $this->userAuthorizationChecker->init();

        return $this->userAuthorizationChecker->hasRole($this->currentAuthorizationUser, false, $roleName);
    }

    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        $this->userAuthorizationChecker->init();

        return $this->userAuthorizationChecker->getAttribute($this->currentAuthorizationUser, false, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function hasPrivilegeInternal(string $privilegeName, $subject): bool
    {
        $this->userAuthorizationChecker->init();

        return $this->userAuthorizationChecker->hasPrivilege($this->currentAuthorizationUser, $privilegeName, $subject);
    }
}
