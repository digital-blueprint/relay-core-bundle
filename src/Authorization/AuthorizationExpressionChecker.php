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
    private const USER_VARIBLE_NAME = 'user';
    private const DEFAULT_OBJECT_VARIBLE_NAME = 'object';

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var array */
    private $roleExpressions;

    /** @var array */
    private $attributeExpressions;

    /** @var AuthorizationDataMuxer */
    private $dataMux;

    /** @var array */
    private $roleExpressionStack;

    /** @var array */
    private $attributeExpressionStack;

    public function __construct(AuthorizationDataMuxer $dataMux)
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->roleExpressions = [];
        $this->attributeExpressions = [];
        $this->dataMux = $dataMux;
        $this->roleExpressionStack = [];
        $this->attributeExpressionStack = [];
    }

    public function setExpressions(array $roleExpressions, array $attributeExpressions)
    {
        $this->roleExpressions = $roleExpressions;
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
    public function isGranted(AuthorizationUser $currentAuthorizationUser, string $rightName, $object, string $objectAlias = null): bool
    {
        if (in_array($rightName, $this->roleExpressionStack, true)) {
            throw new AuthorizationException(sprintf('infinite loop caused by authorization right expression %s detected', $rightName), AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED);
        }
        array_push($this->roleExpressionStack, $rightName);

        try {
            $rightExpression = $this->roleExpressions[$rightName] ?? null;
            if ($rightExpression === null) {
                throw new AuthorizationException(sprintf('right \'%s\' undefined', $rightName), AuthorizationException::PRIVILEGE_UNDEFINED);
            }

            $variables = [
                self::USER_VARIBLE_NAME => $currentAuthorizationUser,
                self::DEFAULT_OBJECT_VARIBLE_NAME => $object,
            ];

            if (!Tools::isNullOrEmpty($objectAlias)) {
                $variables[$objectAlias] = $object;
            }

            return $this->expressionLanguage->evaluate($rightExpression, $variables);
        } finally {
            array_pop($this->roleExpressionStack);
        }
    }
}
