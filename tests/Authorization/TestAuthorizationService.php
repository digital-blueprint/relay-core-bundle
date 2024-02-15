<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\User\DummyUserAttributeProvider;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService extends AbstractAuthorizationService
{
    public static function create(string $currentUserIdentifier = 'testuser', array $currentUserAttributes = []): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        self::setUp($testAuthorizationService, $currentUserIdentifier, $currentUserAttributes);

        return $testAuthorizationService;
    }

    public static function setUp(AbstractAuthorizationService $authorizationService, string $currentUserIdentifier = 'testuser', array $currentUserAttributes = []): void
    {
        $userAttributeProvider = new DummyUserAttributeProvider($currentUserAttributes);
        $userAttributeService = new UserAttributeService(
            new TestUserSession($currentUserIdentifier),
            new UserAttributeMuxer(new UserAttributeProviderProvider([$userAttributeProvider]), new EventDispatcher()));

        $authorizationService->__injectUserAttributeService($userAttributeService);
    }
}
