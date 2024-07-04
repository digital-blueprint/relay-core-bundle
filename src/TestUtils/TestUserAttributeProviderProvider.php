<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderProviderInterface;

class TestUserAttributeProviderProvider implements UserAttributeProviderProviderInterface
{
    private ?TestUserAttributeProvider $testUserAttributeProvider = null;

    public function addUser(string $userIdentifier, array $userAttributes): void
    {
        if ($this->testUserAttributeProvider === null) {
            $this->testUserAttributeProvider = new TestUserAttributeProvider(array_keys($userAttributes));
        }
        $this->testUserAttributeProvider->addUser($userIdentifier, $userAttributes);
    }

    /**
     * @psalm-suppress InvalidReturnType
     */
    public function getAuthorizationDataProviders(): iterable
    {
        /**
         * @psalm-suppress InvalidReturnStatement
         */
        return $this->testUserAttributeProvider !== null ? [$this->testUserAttributeProvider] : [];
    }
}
