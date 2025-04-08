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
    public const TEST_USER_IDENTIFIER = 'testuser';
    public const ADMIN_USER_IDENTIFIER = 'admin';

    public static function create(?string $currentUserIdentifier = self::TEST_USER_IDENTIFIER,
        array $currentUserAttributes = [], array $symfonyUerRoles = [],
        bool $isAuthenticated = true, bool $isServiceAccount = false): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        self::setUp($testAuthorizationService, $currentUserIdentifier, $currentUserAttributes, $symfonyUerRoles,
            $isAuthenticated, $isServiceAccount);

        return $testAuthorizationService;
    }

    public static function setUp(AbstractAuthorizationService $authorizationService,
        ?string $currentUserIdentifier = self::TEST_USER_IDENTIFIER, array $currentUserAttributes = [], array $symfonyUserRoles = [],
        bool $isAuthenticated = true, bool $isServiceAccount = false): void
    {
        $userAttributeProvider = new TestUserAttributeProvider($currentUserAttributes);
        if ($isAuthenticated) {
            $userAttributeProvider->addUser($currentUserIdentifier, $currentUserAttributes);
        }
        $userAttributeService = new UserAttributeService(
            new TestUserSession($currentUserIdentifier, $symfonyUserRoles, $isAuthenticated, $isServiceAccount),
            new UserAttributeMuxer(new UserAttributeProviderProvider([$userAttributeProvider]), new EventDispatcher()));

        $authorizationService->__injectUserAttributeService($userAttributeService);
    }
}
