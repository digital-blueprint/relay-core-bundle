<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\User\UserAttributeException;

/**
 * @internal
 *
 * Provides the user interface available within privilege expressions
 */
class AuthorizationUser
{
    public function __construct(private readonly AbstractAuthorizationService $authorizationService)
    {
    }

    /**
     * @return string|null The current authenticated user's identifier or null if the user is not authenticated or is a system
     */
    public function getIdentifier(): ?string
    {
        return $this->authorizationService->getUserIdentifier();
    }

    /**
     * @return bool Indicates whether the current user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->authorizationService->isAuthenticated();
    }

    /**
     * @throws AuthorizationException
     */
    public function getAttribute(string $attributeName, mixed $defaultValue = null): mixed
    {
        return $this->authorizationService->getAttribute($attributeName, $defaultValue);
    }

    /**
     * @deprecated Since v0.1.188, use isGrantedRole, or isGrantedResourcePermission (for resource dependent permissions) instead
     *
     * @throws AuthorizationException
     */
    public function isGranted(string $policyName, ?object $resource = null): bool
    {
        return $this->authorizationService->isGrantedResourcePermission($policyName, $resource);
    }

    /**
     * @throws AuthorizationException
     */
    public function isGrantedRole(string $roleName): bool
    {
        return $this->authorizationService->isGrantedRole($roleName);
    }

    /**
     * @throws AuthorizationException
     */
    public function isGrantedResourcePermission(string $resourcePermissionsName, object $resource): bool
    {
        return $this->authorizationService->isGrantedResourcePermission($resourcePermissionsName, $resource);
    }

    /**
     * @throws AuthorizationException
     * @throws UserAttributeException
     */
    public function get(string $attributeName, mixed $defaultValue = null): mixed
    {
        return $this->authorizationService->getUserAttribute($attributeName, $defaultValue);
    }
}
