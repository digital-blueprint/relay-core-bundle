<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;
use Dbp\Relay\CoreBundle\Helpers\Tools;

/**
 * @internal
 */
class AuthorizationExpressionChecker
{
    private const USER_VARIABLE_NAME = 'user';
    private const DEFAULT_RESOURCE_VARIABLE_NAME = 'resource';

    private ExpressionLanguage $expressionLanguage;

    /** @var string[] */
    private array $policyExpressions;

    /** @var string[] */
    private array $attributeExpressions;

    /** @var string[] */
    private array $policyExpressionStack;

    /** @var string[] */
    private array $attributeExpressionStack;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->policyExpressions = [];
        $this->attributeExpressions = [];
        $this->policyExpressionStack = [];
        $this->attributeExpressionStack = [];
    }

    public function setExpressions(array $policyExpressions, array $attributeExpressions): void
    {
        $this->policyExpressions = $policyExpressions;
        $this->attributeExpressions = $attributeExpressions;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function evalAttributeExpression(AuthorizationUser $currentAuthorizationUser, string $expressionName, $defaultValue = null)
    {
        if (in_array($expressionName, $this->attributeExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization attribute expression %s detected', $expressionName), AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED);
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

    /**
     * Currently, there are no custom privileges. As opposed to roles and attributes, they can't be cached per privilege, since there values depend on the subject.
     * Might be a future requirement.
     *
     * @throws AuthorizationException
     */
    public function isGranted(AuthorizationUser $currentAuthorizationUser, string $policyName, $resource, ?string $resourceAlias = null): bool
    {
        if (in_array($policyName, $this->policyExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization right expression %s detected', $policyName), AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED);
        }
        array_push($this->policyExpressionStack, $policyName);

        try {
            $policyExpression = $this->policyExpressions[$policyName] ?? null;
            if ($policyExpression === null) {
                throw new AuthorizationException(sprintf('policy \'%s\' undefined', $policyName), AuthorizationException::POLICY_UNDEFINED);
            }

            // shortcuts for popular (default) policies:
            if ($policyExpression === 'true') {
                return true;
            } elseif ($policyExpression === 'false') {
                return false;
            }

            $variables = [
                self::USER_VARIABLE_NAME => $currentAuthorizationUser,
                self::DEFAULT_RESOURCE_VARIABLE_NAME => $resource,
            ];

            if (!Tools::isNullOrEmpty($resourceAlias)) {
                $variables[$resourceAlias] = $resource;
            }

            return $this->expressionLanguage->evaluate($policyExpression, $variables);
        } finally {
            array_pop($this->policyExpressionStack);
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

    public function isPolicyExpressionDefined(string $policyExpressionName): bool
    {
        return isset($this->policyExpressions[$policyExpressionName]);
    }

    /**
     * @return string[]
     */
    public function getPolicyExpressionNames(): array
    {
        return array_keys($this->policyExpressions);
    }
}
