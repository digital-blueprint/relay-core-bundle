<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Authorization\ExpressionLanguage\ExpressionLanguage;

class AuthorizationExpressionChecker
{
    public const RIGHTS_CONFIG_ATTRIBUTE = 'rights';
    public const ATTRIBUTES_CONFIG_ATTRIBUTE = 'attributes';

    private const MAX_NUM_CALLS = 16;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var array */
    private $rightExpressions;

    /** @var array */
    private $attributeExpressions;

    /** @var int */
    private $callCounter;

    /** @var AuthorizationDataMuxer */
    private $dataMux;

    public function __construct(AuthorizationDataMuxer $dataMux)
    {
        $this->expressionLanguage = new ExpressionLanguage();

        $this->rightExpressions = [];
        $this->attributeExpressions = [];
        $this->dataMux = $dataMux;
    }

    public function setConfig(array $config)
    {
        $this->loadExpressions($config[self::RIGHTS_CONFIG_ATTRIBUTE] ?? [], $this->rightExpressions);
        $this->loadExpressions($config[self::ATTRIBUTES_CONFIG_ATTRIBUTE] ?? [], $this->attributeExpressions);
    }

    public function init()
    {
        $this->callCounter = 0;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(AuthorizationUser $currentAuthorizationUser, string $attributeName, $defaultValue = null)
    {
        $this->tryIncreaseRecursionCounter($attributeName);

        if (($attributeExpression = $this->attributeExpressions[$attributeName] ?? null) !== null) {
            $attribute = $this->expressionLanguage->evaluate($attributeExpression, [
                'user' => $currentAuthorizationUser,
            ]);
        } else {
            throw new AuthorizationException(sprintf('attribute \'%s\' undefined', $attributeName), AuthorizationException::ATTRIBUTE_UNDEFINED);
        }

        return $attribute ?? $defaultValue;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getCustomAttribute(AuthorizationUser $currentAuthorizationUser, string $attributeName, $defaultValue = null)
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
        $this->tryIncreaseRecursionCounter($rightName);

        $rightExpression = $this->rightExpressions[$rightName] ?? null;
        if ($rightExpression === null) {
            throw new AuthorizationException(sprintf('right \'%s\' undefined', $rightName), AuthorizationException::PRIVILEGE_UNDEFINED);
        }

        return $this->expressionLanguage->evaluate($rightExpression, [
            'user' => $currentAuthorizationUser,
            'subject' => $subject,
        ]);
    }

    private function loadExpressions(array $expressions, array &$target): void
    {
        foreach ($expressions as $name => $expression) {
            $target[$name] = $expression;
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function tryIncreaseRecursionCounter(string $hint): void
    {
        if (++$this->callCounter > self::MAX_NUM_CALLS) {
            throw new AuthorizationException(sprintf('possible infinite loop in authorization expression detected (%s)', $hint), AuthorizationException::INFINITE_LOOP_DETECTED);
        }
    }
}
