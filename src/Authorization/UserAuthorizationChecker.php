<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class UserAuthorizationChecker
{
    private const MAX_NUM_CALLS = 16;

    /** @var string */
    private $currentUserIdentifier;

    /** @var iterable */
    private $authorizationDataProviders;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var array */
    private $customRoles;

    /** @var array */
    private $customAttributes;

    /** @var array */
    private $privilegeExpressions;

    /** @var array */
    private $roleExpressions;

    /** @var array */
    private $attributeExpressions;

    /** @var int */
    private $callCounter;

    public function __construct(UserSessionInterface $userSession, AuthorizationDataProviderProvider $authorizationDataProviderProvider)
    {
        $this->currentUserIdentifier = $userSession->getUserIdentifier();
        $this->authorizationDataProviders = $authorizationDataProviderProvider->getAuthorizationDataProviders();
        $this->expressionLanguage = new ExpressionLanguage();

        $this->customRoles = [];
        $this->customAttributes = [];

        $this->roleExpressions = [];
        $this->privilegeExpressions = [];
        $this->attributeExpressions = [];
    }

    public function setConfig(array $config)
    {
        $this->loadExpressions($config[AuthorizationService::ROLES_CONFIG_ATTRIBUTE], $this->roleExpressions);
        $this->loadExpressions($config[AuthorizationService::PRIVILEGES_CONFIG_ATTRIBUTE], $this->privilegeExpressions);
        $this->loadExpressions($config[AuthorizationService::ATTRIBUTES_CONFIG_ATTRIBUTE], $this->attributeExpressions);
    }

    public function resetRecursionCounter()
    {
        $this->callCounter = 0;
    }

    public function hasRole(AuthorizationUser $currentAuthorizationUser, bool $areCustomRolesAllowed, string $roleName): bool
    {
        $this->tryIncreaseRecursionCounter($roleName);

        $hasRole = null;

        if (($roleExpression = $this->roleExpressions[$roleName] ?? null) !== null) {
            $hasRole = $this->expressionLanguage->evaluate($roleExpression, [
                'user' => $currentAuthorizationUser,
            ]);
        } elseif ($areCustomRolesAllowed) {
            if (array_key_exists($roleName, $this->customRoles) === false) {
                $this->loadRole($roleName);
            }

            $hasRole = $this->customRoles[$roleName] ?? null;
        }

        if ($hasRole === null) {
            throw new AuthorizationException(sprintf('role \'%s\' undefined', $roleName), AuthorizationException::ROLE_UNDEFINED);
        }

        return $hasRole;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(AuthorizationUser $currentAuthorizationUser, bool $areCustomAttributesAllowed, string $attributeName, $defaultValue = null)
    {
        $this->tryIncreaseRecursionCounter($attributeName);

        $attributeDefined = false;

        if (($attributeExpression = $this->attributeExpressions[$attributeName] ?? null) !== null) {
            $attributeDefined = true;
            $attribute = $this->expressionLanguage->evaluate($attributeExpression, [
                'user' => $currentAuthorizationUser,
            ]);
        } elseif ($areCustomAttributesAllowed) {
            if (array_key_exists($attributeName, $this->customAttributes) === false) {
                $this->loadAttribute($attributeName);
            }

            if (isset($this->customAttributes[$attributeName])) {
                $attributeDefined = true;
                $attribute = $this->customAttributes[$attributeName];
            }
        }

        if ($attributeDefined === false) {
            throw new AuthorizationException(sprintf('attribute \'%s\' undefined', $attributeName), AuthorizationException::ATTRIBUTE_UNDEFINED);
        }

        return $attribute ?? $defaultValue;
    }

    /**
     * Currently, there are no custom privileges. As opposed to roles and attributes, they can't be cached per privilege, since there values depend on the subject.
     * Might be a future requirement.
     *
     * @throws AuthorizationException
     */
    public function hasPrivilege(AuthorizationUser $currentAuthorizationUser, string $privilegeName, $subject): bool
    {
        $this->tryIncreaseRecursionCounter($privilegeName);

        $privilegeExpression = $this->privilegeExpressions[$privilegeName] ?? null;
        if ($privilegeExpression === null) {
            throw new AuthorizationException(sprintf('privilege \'%s\' undefined', $privilegeName), AuthorizationException::PRIVILEGE_UNDEFINED);
        }

        return $this->expressionLanguage->evaluate($privilegeExpression, [
            'user' => $currentAuthorizationUser,
            'subject' => $subject,
        ]);
    }

    private function loadExpressions(array $expressions, array &$target)
    {
        foreach ($expressions as $expression) {
            $target[$expression[AuthorizationService::NAME_CONFIG_ATTRIBUTE]] = $expression[AuthorizationService::EXPRESSION_CONFIG_ATTRIBUTE];
        }
    }

    private function loadRole(string $roleName)
    {
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableRoles = $authorizationDataProvider->getAvailableRoles();
            if (in_array($roleName, $availableRoles, true)) {
                $this->loadUserDataFromAuthorizationProvider($authorizationDataProvider);
                break;
            }
        }
    }

    private function loadAttribute(string $attributeName)
    {
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableAttributes = $authorizationDataProvider->getAvailableAttributes();
            if (in_array($attributeName, $availableAttributes, true)) {
                $this->loadUserDataFromAuthorizationProvider($authorizationDataProvider);
                break;
            }
        }
    }

    private function loadUserDataFromAuthorizationProvider(AuthorizationDataProviderInterface $authorizationDataProvider)
    {
        $userRoles = [];
        $userAttributes = [];

        if (Tools::isNullOrEmpty($this->currentUserIdentifier) === false) {
            $authorizationDataProvider->getUserData($this->currentUserIdentifier, $userRoles, $userAttributes);
        }

        foreach ($authorizationDataProvider->getAvailableAttributes() as $availableAttribute) {
            $this->customAttributes[$availableAttribute] = $userAttributes[$availableAttribute] ?? null;
        }

        foreach ($authorizationDataProvider->getAvailableRoles() as $availableRole) {
            $this->customRoles[$availableRole] = in_array($availableRole, $userRoles, true);
        }
    }

    private function tryIncreaseRecursionCounter(string $hint)
    {
        if (++$this->callCounter > self::MAX_NUM_CALLS) {
            throw new AuthorizationException(sprintf('possible infinite loop in authorization expression detected (%s)', $hint), AuthorizationException::INFINITE_LOOP_DETECTED);
        }
    }
}
