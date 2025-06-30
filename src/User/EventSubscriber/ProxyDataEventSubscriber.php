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

    private static bool $isCurrentlyActive = false;

    public function __construct(private readonly UserAttributeMuxer $userAttributeMuxer)
    {
    }

    /**
     * Indicates that the event subscriber is currently busy handling a proxy data event.
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

    /**
     * idled in the course of changing the UserAttributeProviderInterface from "get-all-available-attributes" to
     * "has-get-attribute".
     */
    private function getAvailableAttributes(): array
    {
        return [];
    }

    /**
     * idled in the course of changing the UserAttributeProviderInterface from "get-all-available-attributes" to
     * "has-get-attribute".
     */
    private function getUserAttributes(string $userIdentifier): array
    {
        return [];
    }
}
