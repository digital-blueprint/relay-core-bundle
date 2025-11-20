<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractAuthorizationService
{
    private AuthorizationExpressionChecker $authorizationExpressionChecker;
    private AuthorizationUser $currentAuthorizationUser;
    private ?UserAttributeService $userAttributeService = null;

    public function __construct()
    {
        $this->authorizationExpressionChecker = new AuthorizationExpressionChecker();
        $this->currentAuthorizationUser = new AuthorizationUser($this);
    }

    #[Required]
    public function __injectUserAttributeService(UserAttributeService $userAttributeService): void
    {
        $this->userAttributeService = $userAttributeService;
    }

    /**
     * Resets the internal state (e.g. request caches).
     * Should be called between requests when performing multiple requests in a single test case.
     */
    public function reset(): void
    {
        $this->userAttributeService?->reset();
    }

    /**
     * Method for bundle config injection. Don't call in your code  (use @see AbstractAuthorizationService::configure() instead).
     */
    public function setConfig(array $config): void
    {
        if ($authorizationConfig = $config[AuthorizationConfigDefinition::AUTHORIZATION_CONFIG_NODE] ?? null) {
            $this->setUpAccessControlPolicies(
                $authorizationConfig[AuthorizationConfigDefinition::ROLES_CONFIG_NODE] ?? [],
                $authorizationConfig[AuthorizationConfigDefinition::RESOURCE_PERMISSIONS_CONFIG_NODE] ?? [],
                $authorizationConfig[AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE] ?? []);
        }
    }

    public function setUpAccessControlPolicies(array $roles = [], array $resourcePermissions = [], array $attributes = []): void
    {
        $this->authorizationExpressionChecker->addExpressions($roles, $resourcePermissions, $attributes);
    }

    public function isAttributeDefined(string $attributeName): bool
    {
        return $this->authorizationExpressionChecker->isAttributeExpressionDefined($attributeName);
    }

    /**
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        return $this->authorizationExpressionChecker->getAttributeExpressionNames();
    }

    public function isRoleDefined(string $policyName): bool
    {
        return $this->authorizationExpressionChecker->isRoleExpressionDefined($policyName);
    }

    /**
     * @return string[]
     */
    public function getRoleNames(): array
    {
        return $this->authorizationExpressionChecker->getRoleExpressionNames();
    }

    public function isResourcePermissionDefined(string $policyName): bool
    {
        return $this->authorizationExpressionChecker->isResourcePermissionExpressionDefined($policyName);
    }

    /**
     * @return string[]
     */
    public function getResourcePermissionNames(): array
    {
        return $this->authorizationExpressionChecker->getResourcePermissionExpressionNames();
    }

    /**
     * @deprecated Since v0.1.188 use isRoleDefined or isResourcePermissionDefined instead
     */
    public function isPolicyDefined(string $policyName): bool
    {
        return $this->authorizationExpressionChecker->isResourcePermissionExpressionDefined($policyName);
    }

    /**
     * @deprecated Since v0.1.188 use getRoleNames or getResourcePermissionNames instead
     *
     * @return string[]
     */
    public function getPolicyNames(): array
    {
        return $this->authorizationExpressionChecker->getResourcePermissionExpressionNames();
    }

    /**
     * @throws ApiError               HTTP Forbidden exception if access is not granted
     * @throws AuthorizationException If the policy is not declared
     *
     * @deprecated Since v0.1.188, use denyAccessUnlessIsGrantedRole, or denyAccessUnlessIsGrantedResourcePermission
     * (for resource dependent permissions) instead
     *
     * Checks the given policy for the current user and the resource $resource. Throws a 'forbidden' exception if
     * access is not granted.
     */
    public function denyAccessUnlessIsGranted(string $policyName, mixed $resource = null, ?string $resourceAlias = null): void
    {
        if ($this->isGrantedResourcePermissionInternal($policyName, $resource) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @throws ApiError               HTTP Forbidden exception if access is not granted
     * @throws AuthorizationException If the policy is not declared
     *
     * Checks the given policy for the current user and the resource $resource. Throws a 'forbidden' exception if
     * access is not granted.
     */
    public function denyAccessUnlessIsGrantedRole(string $roleName): void
    {
        if ($this->isGrantedRoleInternal($roleName) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @throws ApiError               HTTP Forbidden exception if access is not granted
     * @throws AuthorizationException If the policy is not declared
     *
     * Checks the given policy for the current user and the resource $resource. Throws a 'forbidden' exception if
     * access is not granted.
     */
    public function denyAccessUnlessIsGrantedResourcePermission(string $resourcePermissionName, mixed $resource): void
    {
        if ($this->isGrantedResourcePermissionInternal($resourcePermissionName, $resource) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Returns true if role is granted, false otherwise.
     *
     * @throws AuthorizationException
     */
    public function isGrantedRole(string $roleName): bool
    {
        return $this->isGrantedRoleInternal($roleName);
    }

    /**
     * Returns true if resource permission is granted, false otherwise.
     *
     * @throws AuthorizationException
     */
    public function isGrantedResourcePermission(string $resourcePermissionsName, mixed $resource): bool
    {
        return $this->isGrantedResourcePermissionInternal($resourcePermissionsName, $resource);
    }

    /**
     * @deprecated Since v0.1.188, use isGrantedRole, or isGrantedResourcePermission (for resource dependent permissions) instead
     *
     * Checks the given policy for the current user and the resource $resource. Returns true if access is granted, false otherwise.
     *
     * @throws AuthorizationException If the policy is not declared
     */
    public function isGranted(string $policyName, mixed $resource = null, ?string $resourceAlias = null): bool
    {
        return $this->isGrantedResourcePermissionInternal($policyName, $resource);
    }

    /**
     * Evaluates the attribute expression $attributeExpressionName und returns its result.
     *
     * @param mixed $defaultValue The value to return if the expression evaluates to 'null'
     *
     * @throws AuthorizationException If the attribute is not declared
     */
    public function getAttribute(string $attributeExpressionName, mixed $defaultValue = null): mixed
    {
        return $this->getAttributeInternal($attributeExpressionName, $defaultValue);
    }

    /**
     * Evaluates a custom symfony expression that may access the currently logged-in 'user' variable
     * and the given set of variables.
     *
     * @param array<string, mixed> $variables    The set of defined variables
     * @param mixed                $defaultValue The value to return if the expression evaluates to 'null'
     */
    public function evaluateCustomExpression(
        string $expression, array $variables = [], mixed $defaultValue = null): mixed
    {
        return $this->authorizationExpressionChecker->evaluateCustomExpression(
            $this->currentAuthorizationUser, $expression, $variables, $defaultValue);
    }

    /**
     * Returns the identifier of the currently logged-in user.
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userAttributeService->getCurrentUserIdentifier();
    }

    /**
     * Indicates whether the current user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->userAttributeService->isCurrentUserAuthenticated();
    }

    /**
     * Gets a user attribute for the currently logged-in user.
     *
     * @param mixed|null $defaultValue The value to return if the user attribute is declared but not specified for the current user
     *
     * @throws UserAttributeException If the user attribute is undeclared
     */
    public function getUserAttribute(string $userAttributeName, mixed $defaultValue = null): mixed
    {
        return $this->userAttributeService->getCurrentUserAttribute($userAttributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        return $this->authorizationExpressionChecker->evalAttributeExpression($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedRoleInternal(string $roleName): bool
    {
        return $this->authorizationExpressionChecker->isGrantedRole($this->currentAuthorizationUser, $roleName);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedResourcePermissionInternal(string $resourcePermission, mixed $resource): bool
    {
        return $this->authorizationExpressionChecker->isGrantedResourcePermission($this->currentAuthorizationUser, $resourcePermission, $resource);
    }
}
