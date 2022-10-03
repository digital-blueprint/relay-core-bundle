<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationAwareService
{
    /** @var Authorization */
    private $authorization;

    public function __construct(Authorization $authorization)
    {
        $this->authorization = $authorization;
    }

    protected function addPrivileges(array $privileges)
    {
        $this->authorization->addPrivileges($privileges);
    }

    /**
     * @throws AuthorizationException
     */
    public function hasPrivilege(string $privilegeName, $subject = null): bool
    {
        return $this->authorization->hasPrivilege($privilegeName, $subject);
    }

    /**
     * @throws AuthorizationException
     * @throws ApiError
     */
    public function denyAccessUnlessHasPrivilege(string $privilegeName, $subject = null): void
    {
        if (!$this->authorization->hasPrivilege($privilegeName, $subject)) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing privilege.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function hasRole(string $roleName): bool
    {
        return $this->authorization->hasRole($roleName);
    }

    /**
     * @throws AuthorizationException
     */
    public function denyAccessUnlessHasRole(string $roleName): void
    {
        if (!$this->authorization->hasRole($roleName)) {
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
        return $this->authorization->getAttribute($attributeName);
    }
}
