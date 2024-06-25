<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;

class TestUserAttributeProvider implements UserAttributeProviderInterface
{
    /**
     * @var array
     *
     * Mpping user identifiers to user attribute maps (attribute name => attribute value)
     */
    private $userAttributes = [];

    /**
     * @var array
     *
     * Mapping available attribute names to default values
     */
    private $defaultAttributes;

    public function __construct(array $defaultAttributes = [])
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
}
