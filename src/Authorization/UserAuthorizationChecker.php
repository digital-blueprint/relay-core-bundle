<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class UserAuthorizationChecker
{
    public const RIGHTS_CONFIG_ATTRIBUTE = 'rights';
    public const ATTRIBUTES_CONFIG_ATTRIBUTE = 'attributes';

    private const MAX_NUM_CALLS = 16;

    /** @var string */
    private $currentUserIdentifier;

    /** @var iterable */
    private $authorizationDataProviders;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var array */
    private $customAttributes;

    /** @var array */
    private $rightExpressions;

    /** @var array */
    private $attributeExpressions;

    /** @var int */
    private $callCounter;

    public function __construct(UserSessionInterface $userSession, AuthorizationDataProviderProvider $authorizationDataProviderProvider)
    {
        $this->currentUserIdentifier = $userSession->getUserIdentifier();
        $this->authorizationDataProviders = $authorizationDataProviderProvider->getAuthorizationDataProviders();
        $this->expressionLanguage = new ExpressionLanguage();

        $this->customAttributes = [];

        $this->rightExpressions = [];
        $this->attributeExpressions = [];
    }

    public function setConfig(array $config)
    {
        $this->loadExpressions($config[self::RIGHTS_CONFIG_ATTRIBUTE], $this->rightExpressions);
        $this->loadExpressions($config[self::ATTRIBUTES_CONFIG_ATTRIBUTE], $this->attributeExpressions);
    }

    public function init()
    {
        $this->callCounter = 0;
    }

    public function getCurrentUserIdentifier(): string
    {
        return $this->currentUserIdentifier;
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
        if (array_key_exists($attributeName, $this->customAttributes) === false) {
            $this->loadCustomAttribute($attributeName);
        }

        return $this->customAttributes[$attributeName] ?? $defaultValue;
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
    private function loadCustomAttribute(string $attributeName): void
    {
        $wasFound = false;
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableAttributes = $authorizationDataProvider->getAvailableAttributes();
            if (in_array($attributeName, $availableAttributes, true)) {
                $this->loadUserAttributesFromAuthorizationProvider($authorizationDataProvider);
                $wasFound = true;
                break;
            }
        }

        if ($wasFound === false) {
            throw new AuthorizationException(sprintf('custom attribute \'%s\' undefined', $attributeName), AuthorizationException::ATTRIBUTE_UNDEFINED);
        }
    }

    private function loadUserAttributesFromAuthorizationProvider(AuthorizationDataProviderInterface $authorizationDataProvider): void
    {
        $userAttributes = [];

        if (Tools::isNullOrEmpty($this->currentUserIdentifier) === false) {
            $userAttributes = $authorizationDataProvider->getUserAttributes($this->currentUserIdentifier);
        }

        foreach ($authorizationDataProvider->getAvailableAttributes() as $availableAttribute) {
            $this->customAttributes[$availableAttribute] = $userAttributes[$availableAttribute] ?? null;
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
