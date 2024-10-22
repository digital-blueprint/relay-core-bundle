<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
class AuthorizationExpressionChecker
{
    private const USER_VARIABLE_NAME = 'user';
    private const DEFAULT_RESOURCE_VARIABLE_NAME = 'resource';

    private ExpressionLanguage $expressionLanguage;

    /** @var string[] */
    private array $roleExpressions = [];

    /** @var string[] */
    private array $resourcePermissionExpressions = [];

    /** @var string[] */
    private array $attributeExpressions = [];

    /** @var string[] */
    private array $resourcePermissionExpressionStack = [];

    /** @var string[] */
    private array $roleExpressionStack = [];

    /** @var string[] */
    private array $attributeExpressionStack = [];

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function setExpressions(array $roleExpressions, array $resourcePermissionExpressions, array $attributeExpressions): void
    {
        $this->roleExpressions = $roleExpressions;
        $this->resourcePermissionExpressions = $resourcePermissionExpressions;
        $this->attributeExpressions = $attributeExpressions;
    }

    public function evalAttributeExpression(AuthorizationUser $currentAuthorizationUser,
        string $expressionName, mixed $defaultValue = null): mixed
    {
        if (in_array($expressionName, $this->attributeExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization attribute expression %s detected', $expressionName), AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED);
        }
        array_push($this->attributeExpressionStack, $expressionName);

        try {
            if (($expression = $this->attributeExpressions[$expressionName] ?? null) !== null) {
                $result = $this->expressionLanguage->evaluate($expression, [
                    'user' => $currentAuthorizationUser,
                ]);
            } else {
                throw new AuthorizationException(sprintf('expression \'%s\' undefined', $expressionName), AuthorizationException::ATTRIBUTE_UNDEFINED);
            }

            return $result ?? $defaultValue;
        } finally {
            array_pop($this->attributeExpressionStack);
        }
    }

    public function isGrantedRole(AuthorizationUser $currentAuthorizationUser, string $roleName)
    {
        if (in_array($roleName, $this->roleExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization role expression %s detected', $roleName),
                AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED);
        }
        array_push($this->roleExpressionStack, $roleName);

        try {
            $roleExpression = $this->roleExpressions[$roleName] ?? null;
            if ($roleExpression === null) {
                throw new AuthorizationException(sprintf('role \'%s\' undefined', $roleName), AuthorizationException::ROLE_UNDEFINED);
            }

            // shortcuts for popular (default) policies:
            if ($roleExpression === 'true') {
                return true;
            } elseif ($roleExpression === 'false') {
                return false;
            }

            $variables = [
                self::USER_VARIABLE_NAME => $currentAuthorizationUser,
            ];

            return $this->expressionLanguage->evaluate($roleExpression, $variables);
        } finally {
            array_pop($this->roleExpressionStack);
        }
    }

    /**
     * Currently, there are no custom privileges. As opposed to roles and attributes, they can't be cached per privilege, since there values depend on the subject.
     * Might be a future requirement.
     *
     * @throws AuthorizationException
     */
    public function isGrantedResourcePermission(AuthorizationUser $currentAuthorizationUser, string $resourcePermissionName, ?object $resource): bool
    {
        if (in_array($resourcePermissionName, $this->resourcePermissionExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization right expression %s detected', $resourcePermissionName),
                AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED);
        }
        array_push($this->resourcePermissionExpressionStack, $resourcePermissionName);

        try {
            $resourcePermissionExpression = $this->resourcePermissionExpressions[$resourcePermissionName] ?? null;
            if ($resourcePermissionExpression === null) {
                throw new AuthorizationException(sprintf('resource permission \'%s\' undefined', $resourcePermissionName),
                    AuthorizationException::RESOURCE_PERMISSION_UNDEFINED);
            }

            // shortcuts for popular (default) policies:
            if ($resourcePermissionExpression === 'true') {
                return true;
            } elseif ($resourcePermissionExpression === 'false') {
                return false;
            }

            $variables = [
                self::USER_VARIABLE_NAME => $currentAuthorizationUser,
                self::DEFAULT_RESOURCE_VARIABLE_NAME => $resource,
            ];

            return $this->expressionLanguage->evaluate($resourcePermissionExpression, $variables);
        } finally {
            array_pop($this->resourcePermissionExpressionStack);
        }
    }

    public function isAttributeExpressionDefined(string $attributeExpressionName): bool
    {
        return isset($this->attributeExpressions[$attributeExpressionName]);
    }

    /**
     * @return string[]
     */
    public function getAttributeExpressionNames(): array
    {
        return array_keys($this->attributeExpressions);
    }

    public function isRoleExpressionDefined(string $policyExpressionName): bool
    {
        return isset($this->roleExpressions[$policyExpressionName]);
    }

    /**
     * @return string[]
     */
    public function getRoleExpressionNames(): array
    {
        return array_keys($this->roleExpressions);
    }

    public function isResourcePermissionExpressionDefined(string $policyExpressionName): bool
    {
        return isset($this->resourcePermissionExpressions[$policyExpressionName]);
    }

    /**
     * @return string[]
     */
    public function getResourcePermissionExpressionNames(): array
    {
        return array_keys($this->resourcePermissionExpressions);
    }
}
