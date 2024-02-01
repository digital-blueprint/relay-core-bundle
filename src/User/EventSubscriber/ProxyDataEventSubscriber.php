<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\EventSubscriber;

use Dbp\Relay\CoreBundle\ProxyApi\AbstractProxyDataEventSubscriber;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;

class ProxyDataEventSubscriber extends AbstractProxyDataEventSubscriber
{
    public const NAMESPACE = 'core';

    public const GET_AVAILABLE_ATTRIBUTES_FUNCTION_NAME = 'getAvailableAttributes';
    public const GET_USER_ATTRIBUTES_FUNCTION_NAME = 'getUserAttributes';

    public const USER_ID_PARAMETER_NAME = 'userId';

    /** @var UserAttributeMuxer */
    private $userAttributeMuxer;

    /** @var bool */
    private static $isCurrentlyActive = false;

    public function __construct(UserAttributeMuxer $userAttributeMuxer)
    {
        $this->userAttributeMuxer = $userAttributeMuxer;
    }

    /**
     * Indicates, that the event subscriber is currently busy handling a proxy data event.
     */
    public static function isCurrentlyActive(): bool
    {
        return self::$isCurrentlyActive;
    }

    protected static function getSubscribedNamespace(): string
    {
        return self::NAMESPACE;
    }

    protected static function getAvailableFunctionSignatures(): array
    {
        return [
            self::GET_AVAILABLE_ATTRIBUTES_FUNCTION_NAME => [],
            self::GET_USER_ATTRIBUTES_FUNCTION_NAME => [self::USER_ID_PARAMETER_NAME],
        ];
    }

    /**
     * @throws \Exception
     */
    protected function callFunction(string $functionName, array $arguments): ?array
    {
        try {
            self::$isCurrentlyActive = true;
            $returnValue = null;

            switch ($functionName) {
                case self::GET_AVAILABLE_ATTRIBUTES_FUNCTION_NAME:
                    $returnValue = $this->getAvailableAttributes();
                    break;
                case self::GET_USER_ATTRIBUTES_FUNCTION_NAME:
                    $returnValue = $this->getUserAttributes($arguments[self::USER_ID_PARAMETER_NAME]);
                    break;
            }

            return $returnValue;
        } finally {
            self::$isCurrentlyActive = false;
        }
    }

    private function getAvailableAttributes(): array
    {
        return $this->userAttributeMuxer->getAvailableAttributes();
    }

    private function getUserAttributes(string $userIdentifier): array
    {
        $userAttributes = [];

        foreach ($this->userAttributeMuxer->getAvailableAttributes() as $attributeName) {
            $userAttributes[$attributeName] = $this->userAttributeMuxer->getAttribute($userIdentifier, $attributeName);
        }

        return $userAttributes;
    }
}
