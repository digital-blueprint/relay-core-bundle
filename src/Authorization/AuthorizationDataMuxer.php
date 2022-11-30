<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationDataMuxer
{
    /** @var iterable */
    private $authorizationDataProviders;

    /** @var array */
    private $attributes;

    /**
     * @param iterable<AuthorizationDataProviderInterface> $authorizationDataProviders
     */
    public function __construct(iterable $authorizationDataProviders)
    {
        $this->authorizationDataProviders = $authorizationDataProviders;
        $this->attributes = [];
    }

    private function loadUserAttributesFromAuthorizationProvider(?string $userIdentifier, AuthorizationDataProviderInterface $authorizationDataProvider): void
    {
        $userAttributes = $authorizationDataProvider->getUserAttributes($userIdentifier);

        foreach ($authorizationDataProvider->getAvailableAttributes() as $availableAttribute) {
            if (array_key_exists($availableAttribute, $userAttributes)) {
                $this->attributes[$availableAttribute] = $userAttributes[$availableAttribute];
            }
        }
    }

    /**
     * Returns an array of available attributes.
     *
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        $res = [];
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableAttributes = $authorizationDataProvider->getAvailableAttributes();
            $res = array_merge($res, $availableAttributes);
        }

        return $res;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getAttribute(?string $userIdentifier, string $attributeName, $defaultValue = null)
    {
        if (array_key_exists($attributeName, $this->attributes) === false) {
            $this->loadAttribute($userIdentifier, $attributeName);
        }

        return $this->attributes[$attributeName] ?? $defaultValue;
    }

    /**
     * @throws AuthorizationException
     */
    private function loadAttribute(?string $userIdentifier, string $attributeName): void
    {
        $wasFound = false;
        foreach ($this->authorizationDataProviders as $authorizationDataProvider) {
            $availableAttributes = $authorizationDataProvider->getAvailableAttributes();
            if (in_array($attributeName, $availableAttributes, true)) {
                $this->loadUserAttributesFromAuthorizationProvider($userIdentifier, $authorizationDataProvider);
                $wasFound = true;
                break;
            }
        }

        if ($wasFound === false) {
            throw new AuthorizationException(sprintf('custom attribute \'%s\' undefined', $attributeName), AuthorizationException::ATTRIBUTE_UNDEFINED);
        }
    }
}
