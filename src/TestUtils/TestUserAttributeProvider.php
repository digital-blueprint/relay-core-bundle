<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;

class TestUserAttributeProvider implements UserAttributeProviderInterface
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

    public function addUser(?string $userIdentifier, array $userAttributes = []): void
    {
        $this->userAttributes[$userIdentifier] = $userAttributes;
    }

    public function getUserAttributes(?string $userIdentifier): array
    {
        if (($userAttributes = $this->userAttributes[$userIdentifier] ?? null) === null) {
            return $this->defaultAttributes;
        }

        foreach ($this->defaultAttributes as $attributeName => $defaultValue) {
            if (!isset($userAttributes[$attributeName])) {
                $userAttributes[$attributeName] = $defaultValue;
            }
        }

        return $userAttributes;
    }

    /**
     * @throws UserAttributeException
     */
    public function getUserAttribute(?string $userIdentifier, string $name): mixed
    {
        if ($userAttributes = $this->userAttributes[$userIdentifier] ?? null) {
            if (array_key_exists($name, $userAttributes)) {
                return $userAttributes[$name];
            }
        }

        if (array_key_exists($name, $this->defaultAttributes)) {
            return $this->defaultAttributes[$name];
        }

        throw new UserAttributeException("User attribute '$name' is undefined",
            UserAttributeException::USER_ATTRIBUTE_UNDEFINED);
    }

    public function hasUserAttribute(string $name): bool
    {
        return array_key_exists($name, $this->defaultAttributes);
    }
}
