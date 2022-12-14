<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
class AuthorizationExpressionChecker
{
    public const RIGHTS_CONFIG_NODE = 'rights';
    public const ATTRIBUTES_CONFIG_NODE = 'attributes';

    private const MAX_NUM_CALLS = 16;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var array */
    private $rightExpressions;

    /** @var array */
    private $attributeExpressions;

    /** @var AuthorizationDataMuxer */
    private $dataMux;

    /** @var array */
    private $rightExpressionStack;

    /** @var array */
    private $attributeExpressionStack;

    public function __construct(AuthorizationDataMuxer $dataMux)
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->rightExpressions = [];
        $this->attributeExpressions = [];
        $this->dataMux = $dataMux;
        $this->rightExpressionStack = [];
        $this->attributeExpressionStack = [];
    }

    public function setConfig(array $config)
    {
        $this->loadExpressions($config[self::RIGHTS_CONFIG_NODE] ?? [], $this->rightExpressions);
        $this->loadExpressions($config[self::ATTRIBUTES_CONFIG_NODE] ?? [], $this->attributeExpressions);
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
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getUserAttribute(AuthorizationUser $currentAuthorizationUser, string $attributeName, $defaultValue = null)
    {
        return $this->dataMux->getAttribute($currentAuthorizationUser->getIdentifier(), $attributeName, $defaultValue);
    }

    /**
     * Currently, there are no custom privileges. As opposed to roles and attributes, they can't be cached per privilege, since there values depend on the subject.
     * Might be a future requirement.
     *
     * @throws AuthorizationException
     */
    public function isGranted(AuthorizationUser $currentAuthorizationUser, string $rightName, $subject): bool
    {
        if (in_array($rightName, $this->rightExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization right expression %s detected', $rightName), AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED);
        }
        array_push($this->rightExpressionStack, $rightName);

        try {
            $rightExpression = $this->rightExpressions[$rightName] ?? null;
            if ($rightExpression === null) {
                throw new AuthorizationException(sprintf('right \'%s\' undefined', $rightName), AuthorizationException::PRIVILEGE_UNDEFINED);
            }

            return $this->expressionLanguage->evaluate($rightExpression, [
                'user' => $currentAuthorizationUser,
                'subject' => $subject,
            ]);
        } finally {
            array_pop($this->rightExpressionStack);
        }
    }

    private function loadExpressions(array $expressions, array &$target): void
    {
        foreach ($expressions as $name => $expression) {
            $target[$name] = $expression;
        }
    }
}
