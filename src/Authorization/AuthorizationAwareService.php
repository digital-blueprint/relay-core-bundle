<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationAwareService
{
    /** @var UserAuthorizationChecker */
    private $userAuthorizationChecker;

    public function __construct(UserAuthorizationChecker $userAuthorizationChecker)
    {
        $this->userAuthorizationChecker = $userAuthorizationChecker;
    }

    protected function addPrivileges(array $privileges)
    {
        $this->userAuthorizationChecker->addPrivileges($privileges);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject = null): bool
    {
        return $this->userAuthorizationChecker->hasPrivilege($privilegeName, $subject);
    }

    /**
     * @throws AuthorizationException
     * @throws ApiError
     */
    public function denyAccessUnlessHasPrivilege(string $privilegeName, $subject = null): void
    {
        if (!$this->userAuthorizationChecker->hasPrivilege($privilegeName, $subject)) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing privilege.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function hasRole(string $roleName): bool
    {
        return $this->userAuthorizationChecker->hasRole($roleName);
    }

    /**
     * @throws AuthorizationException
     */
    public function denyAccessUnlessHasRole(string $roleName): void
    {
        if (!$this->userAuthorizationChecker->hasRole($roleName)) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing role.');
        }
    }

    /**
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getAttribute(string $attributeName)
    {
        return $this->userAuthorizationChecker->getAttribute($attributeName);
    }
}
