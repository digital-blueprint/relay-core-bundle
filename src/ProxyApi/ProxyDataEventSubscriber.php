<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\ProxyApi;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Exception;

class ProxyDataEventSubscriber extends AbstractProxyDataEventSubscriber
{
    public const NAMESPACE = 'core';

    public const GET_AVAILABLE_ATTRIBUTES_FUNCTION_NAME = 'getAvailableAttributes';
    public const GET_USER_ATTRIBUTES_FUNCTION_NAME = 'getUserAttributes';

    public const USER_ID_PARAMETER_NAME = 'userId';

    /**
     * @var AuthorizationDataProviderProvider
     */
    private $provider;

    /** @var bool */
    private static $isCurrentlyActive = false;

    public function __construct(AuthorizationDataProviderProvider $provider)
    {
        $this->provider = $provider;
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
     * @throws Exception
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
        $availableAttributes = [];

        foreach ($this->provider->getAuthorizationDataProviders() as $provider) {
            $availableAttributes = array_merge($availableAttributes, $provider->getAvailableAttributes());
        }

        return $availableAttributes;
    }

    private function getUserAttributes(string $userId): array
    {
        $userAttributes = [];

        foreach ($this->provider->getAuthorizationDataProviders() as $provider) {
            $userAttributes = array_merge($userAttributes, $provider->getUserAttributes($userId));
        }

        return $userAttributes;
    }
}
