<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\User\AbstractUserAttributeService;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuthorizationService extends AbstractUserAttributeService
{
    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    /** @var array|null */
    private $config;

    public function __construct()
    {
        parent::__construct();

        $this->userAuthorizationChecker = new AuthorizationExpressionChecker();
        $this->currentAuthorizationUser = new AuthorizationUser($this);

        $this->loadConfig();
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

    /**
     * Checks the given policy for the current user and the resource $resource. Throws a 'forbidden' exception if
     * access is not granted.
     *
     * @param mixed $resource
     *
     * @throws ApiError               HTTP Forbidden exception if access is not granted
     * @throws AuthorizationException If the policy is not declared
     */
    public function denyAccessUnlessIsGranted(string $policyName, $resource = null, ?string $resourceAlias = null): void
    {
        if ($this->isGrantedInternal($policyName, $resource, $resourceAlias) === false) {
            throw ApiError::withDetails(Response::HTTP_FORBIDDEN, 'access denied. policy failed: '.$policyName);
        }
    }

    /**
     * Checks the given policy for the current user and the resource $resource. Returns true if access is granted, false otherwise.
     *
     * @param mixed $resource
     *
     * @throws AuthorizationException If the policy is not declared
     */
    public function isGranted(string $policyName, $resource = null, ?string $resourceAlias = null): bool
    {
        return $this->isGrantedInternal($policyName, $resource, $resourceAlias);
    }

    /**
     * @throws ApiError Http Unauthorized if the user is not authenticated
     */
    public function denyAccessUnlessIsAuthenticated()
    {
        if ($this->currentAuthorizationUser->isAuthenticated() === false) {
            throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, 'authentication required');
        }
    }

    /**
     * @deprecated Use isAuthenticated instead
     */
    public function isUserAuthenticated(): bool
    {
        return $this->currentAuthorizationUser->isAuthenticated();
    }

    /**
     * Indicates whether the current user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->currentAuthorizationUser->isAuthenticated();
    }

    /**
     * Evaluates the attribute expression $attributeName und returns its result.
     *
     * @param mixed|null $defaultValue The value to return if the expression evaluates to 'null'
     *
     * @return mixed|null
     *
     * @throws AuthorizationException If the attribute is not declared
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->getAttributeInternal($attributeName, $defaultValue);
    }

    private function loadConfig()
    {
        if ($this->userAuthorizationChecker !== null && $this->config !== null) {
            $roleExpressions = $this->config[AuthorizationConfigDefinition::POLICIES_CONFIG_NODE] ?? [];
            $attributeExpressions = $this->config[AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE] ?? [];

            $this->userAuthorizationChecker->setExpressions($roleExpressions, $attributeExpressions);
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        return $this->userAuthorizationChecker->evalAttributeExpression($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedInternal(string $policyName, $resource, ?string $resourceAlias = null): bool
    {
        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $policyName, $resource, $resourceAlias);
    }
}
