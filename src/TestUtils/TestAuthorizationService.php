<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService extends AbstractAuthorizationService
{
    public static function create(string $currentUserIdentifier = 'testuser', array $currentUserAttributes = [],
        array $symfonyUerRoles = [], bool $isAuthenticated = true): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        self::setUp($testAuthorizationService, $currentUserIdentifier, $currentUserAttributes, $symfonyUerRoles, $isAuthenticated);

        return $testAuthorizationService;
    }

    public static function setUp(AbstractAuthorizationService $authorizationService, string $currentUserIdentifier = 'testuser',
        array $currentUserAttributes = [], array $symfonyUserRoles = [], bool $isAuthenticated = true): void
    {
        $userAttributeProvider = new DummyUserAttributeProvider($currentUserAttributes);
        $userAttributeService = new UserAttributeService(
            new TestUserSession($currentUserIdentifier, $symfonyUserRoles, $isAuthenticated),
            new UserAttributeMuxer(new UserAttributeProviderProvider([$userAttributeProvider]), new EventDispatcher()));

        $authorizationService->__injectUserAttributeService($userAttributeService);
    }
}
