<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProviderInterface;

class TestUserAttributeProviderProvider implements UserAttributeProviderProviderInterface
{
    /*
     * @var UserAttributeProviderInterface|null
     */
    private $testUserAttributeProvider;

    public function addUser(string $userIdentifier, array $userAttributes): void
    {
        if ($this->testUserAttributeProvider === null) {
            $this->testUserAttributeProvider = new TestUserAttributeProvider(array_keys($userAttributes));
        }
        $this->testUserAttributeProvider->addUser($userIdentifier, $userAttributes);
    }

    public function getAuthorizationDataProviders(): iterable
    {
        if ($this->testUserAttributeProvider !== null) {
            yield $this->testUserAttributeProvider;
        }
    }
}
