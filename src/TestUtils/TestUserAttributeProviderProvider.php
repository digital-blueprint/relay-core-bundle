<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderProviderInterface;

class TestUserAttributeProviderProvider implements UserAttributeProviderProviderInterface
{
    private TestUserAttributeProvider $testUserAttributeProvider;

    public function __construct()
    {
        $this->testUserAttributeProvider = new TestUserAttributeProvider();
    }

    public function setDefaultAttributes(array $defaultAttributes): void
    {
        $this->testUserAttributeProvider->setDefaultAttributes($defaultAttributes);
    }

    public function addUser(string $userIdentifier, array $userAttributes): void
    {
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
        return [$this->testUserAttributeProvider];
    }
}
