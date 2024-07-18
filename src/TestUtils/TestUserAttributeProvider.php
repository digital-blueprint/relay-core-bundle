<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderExInterface;

class TestUserAttributeProvider implements UserAttributeProviderExInterface
{
    /**
     * Mapping user identifiers to user attribute maps (attribute name => attribute value).
     */
    private array $userAttributes = [];

    /**
     * Mapping available attribute names to default values.
     */
    private array $defaultAttributes = [];

    public function __construct(array $defaultAttributes = [])
    {
        $this->defaultAttributes = $defaultAttributes;
    }

    public function setDefaultAttributes(array $defaultAttributes): void
    {
        $this->defaultAttributes = $defaultAttributes;
    }

    public function addUser(string $userIdentifier, array $userAttributes = []): void
    {
        $this->userAttributes[$userIdentifier] = $userAttributes;
    }

    /**
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        return array_keys($this->defaultAttributes);
    }

    public function getUserAttributes(?string $userIdentifier): array
    {
        if ($userIdentifier === null || ($userAttributes = $this->userAttributes[$userIdentifier] ?? null) === null) {
            return $this->defaultAttributes;
        }

        foreach ($this->defaultAttributes as $attributeName => $defaultValue) {
            if (!isset($userAttributes[$attributeName])) {
                $userAttributes[$attributeName] = $defaultValue;
            }
        }

        return $userAttributes;
    }

    public function getUserAttribute(?string $userIdentifier, string $name): mixed
    {
        $attributes = $this->getUserAttributes($userIdentifier);
        if (!array_key_exists($name, $attributes)) {
            throw new UserAttributeException('unknown '.$name, UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
        }

        return $attributes[$name];
    }

    public function hasUserAttribute(string $name): bool
    {
        return in_array($name, $this->getAvailableAttributes(), true);
    }
}
