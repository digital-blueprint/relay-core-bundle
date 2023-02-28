<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuthorizationService
{
    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    /** @var array|null */
    private $config;

    /**
     * @required
     */
    public function _injectServices(UserSessionInterface $userSession, AuthorizationDataMuxer $mux)
    {
        $this->userAuthorizationChecker = new AuthorizationExpressionChecker($mux);
        $this->currentAuthorizationUser = new AuthorizationUser($userSession, $this->userAuthorizationChecker);

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

    public function configure(array $roles = [], array $attributes = []): void
    {
        $this->config = [
            AuthorizationConfigDefinition::ROLES_CONFIG_NODE => $roles,
            AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE => $attributes,
        ];

        $this->loadConfig();
    }

    /**
     * @param mixed $object
     *
     * @throws ApiError
     */
    public function denyAccessUnlessIsGranted(string $rightName, $object = null, string $objectAlias = null): void
    {
        if ($this->isGrantedInternal($rightName, $object, $objectAlias) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing right '.$rightName);
        }
    }

    /**
     * @param mixed $object
     */
    public function isGranted(string $expressionName, $object = null, string $objectAlias = null): bool
    {
        return $this->isGrantedInternal($expressionName, $object, $objectAlias);
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

    private function loadConfig()
    {
        if ($this->userAuthorizationChecker !== null && $this->config !== null) {
            $roleExpressions = $this->config[AuthorizationConfigDefinition::ROLES_CONFIG_NODE] ?? [];
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
    private function isGrantedInternal(string $rightName, $object, string $objectAlias = null): bool
    {
        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $rightName, $object, $objectAlias);
    }
}
