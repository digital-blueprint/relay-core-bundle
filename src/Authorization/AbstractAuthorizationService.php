<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuthorizationService
{
    private UserAttributeService $userAttributeService;
    private AuthorizationExpressionChecker $authorizationExpressionChecker;
    private AuthorizationUser $currentAuthorizationUser;
    private ?array $config = null;

    public function __construct()
    {
        $this->authorizationExpressionChecker = new AuthorizationExpressionChecker();
        $this->currentAuthorizationUser = new AuthorizationUser($this);

        $this->loadConfig();
    }

    /**
     * @required
     */
    public function __injectUserAttributeService(UserAttributeService $userAttributeService): void
    {
        $this->userAttributeService = $userAttributeService;
    }

    /**
     * Method for bundle config injection. Don't call in your code  (use @see AbstractAuthorizationService::configure() instead).
     */
    public function setConfig(array $config)
    {
        $this->config = $config[AuthorizationConfigDefinition::AUTHORIZATION_CONFIG_NODE] ?? [];

        $this->loadConfig();
    }

    public function configure(array $policies = [], array $attributes = []): void
    {
        $this->config = [
            AuthorizationConfigDefinition::POLICIES_CONFIG_NODE => $policies,
            AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE => $attributes,
        ];

        $this->loadConfig();
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

    public function isPolicyDefined(string $policyName): bool
    {
        return $this->authorizationExpressionChecker->isPolicyExpressionDefined($policyName);
    }

    /**
     * @return string[]
     */
    public function getPolicyNames(): array
    {
        return $this->authorizationExpressionChecker->getPolicyExpressionNames();
    }

    /**
     * Checks the given policy for the current user and the resource $resource. Throws a 'forbidden' exception if
     * access is not granted.
     *
     * @throws ApiError               HTTP Forbidden exception if access is not granted
     * @throws AuthorizationException If the policy is not declared
     */
    public function denyAccessUnlessIsGranted(string $policyName, mixed $resource = null, ?string $resourceAlias = null): void
    {
        if ($this->isGrantedInternal($policyName, $resource, $resourceAlias) === false) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'access denied. policy failed: '.$policyName);
        }
    }

    /**
     * Checks the given policy for the current user and the resource $resource. Returns true if access is granted, false otherwise.
     *
     * @throws AuthorizationException If the policy is not declared
     */
    public function isGranted(string $policyName, mixed $resource = null, ?string $resourceAlias = null): bool
    {
        return $this->isGrantedInternal($policyName, $resource, $resourceAlias);
    }

    /**
     * Evaluates the attribute expression $attributeExpressionName und returns its result.
     *
     * @param mixed|null $defaultValue The value to return if the expression evaluates to 'null'
     *
     * @throws AuthorizationException If the attribute is not declared
     */
    public function getAttribute(string $attributeExpressionName, mixed $defaultValue = null): mixed
    {
        return $this->getAttributeInternal($attributeExpressionName, $defaultValue);
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
     * @return mixed|null
     *
     * @throws UserAttributeException If the user attribute is undeclared
     */
    public function getUserAttribute(string $userAttributeName, $defaultValue = null)
    {
        return $this->userAttributeService->getCurrentUserAttribute($userAttributeName, $defaultValue);
    }

    private function loadConfig()
    {
        if ($this->authorizationExpressionChecker !== null && $this->config !== null) {
            $roleExpressions = $this->config[AuthorizationConfigDefinition::POLICIES_CONFIG_NODE] ?? [];
            $attributeExpressions = $this->config[AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE] ?? [];

            $this->authorizationExpressionChecker->setExpressions($roleExpressions, $attributeExpressions);
        }
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
    private function isGrantedInternal(string $policyName, $resource, ?string $resourceAlias = null): bool
    {
        return $this->authorizationExpressionChecker->isGranted($this->currentAuthorizationUser, $policyName, $resource, $resourceAlias);
    }
}
