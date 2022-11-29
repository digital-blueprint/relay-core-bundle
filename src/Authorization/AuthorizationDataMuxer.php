<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

class AuthorizationDataMuxer
{
    /** @var iterable */
    private $authorizationDataProviders;

    /** @var array */
    private $customAttributes;

    /**
     * @param iterable<AuthorizationDataProviderInterface> $authorizationDataProviders
     */
    public function __construct(iterable $authorizationDataProviders)
    {
        $this->authorizationDataProviders = $authorizationDataProviders;
        $this->customAttributes = [];
    }

    private function loadUserAttributesFromAuthorizationProvider(?string $userIdentifier, AuthorizationDataProviderInterface $authorizationDataProvider): void
    {
        $userAttributes = [];

        if ($userIdentifier !== null) {
            $userAttributes = $authorizationDataProvider->getUserAttributes($userIdentifier);
        }

        foreach ($authorizationDataProvider->getAvailableAttributes() as $availableAttribute) {
            $this->customAttributes[$availableAttribute] = $userAttributes[$availableAttribute] ?? null;
        }
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     *
     * @throws AuthorizationException
     */
    public function getCustomAttribute(?string $userIdentifier, string $attributeName, $defaultValue = null)
    {
        if (array_key_exists($attributeName, $this->customAttributes) === false) {
            $this->loadCustomAttribute($userIdentifier, $attributeName);
        }

        return $this->customAttributes[$attributeName] ?? $defaultValue;
    }

    /**
     * @throws AuthorizationException
     */
    private function loadCustomAttribute(?string $userIdentifier, string $attributeName): void
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
