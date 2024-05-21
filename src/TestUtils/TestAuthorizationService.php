<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\TestUtils\Internal\TestUserSession;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService extends AbstractAuthorizationService
{
    public const TEST_USER_IDENTIFIER = 'testuser';
    public const ADMIN_USER_IDENTIFIER = 'admin';
    public const UNAUTHENTICATED_USER_IDENTIFIER = '';

    public static function create(string $currentUserIdentifier = self::TEST_USER_IDENTIFIER, array $currentUserAttributes = [],
        array $symfonyUerRoles = []): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        self::setUp($testAuthorizationService, $currentUserIdentifier, $currentUserAttributes, $symfonyUerRoles);

        return $testAuthorizationService;
    }

    public static function setUp(AbstractAuthorizationService $authorizationService,
        string $currentUserIdentifier = self::TEST_USER_IDENTIFIER, array $currentUserAttributes = [], array $symfonyUserRoles = []): void
    {
        $userAttributeProvider = new TestUserAttributeProvider(array_keys($currentUserAttributes));
        $userAttributeProvider->addUser($currentUserIdentifier, $currentUserAttributes);
        $userAttributeService = new UserAttributeService(
            $currentUserIdentifier !== self::UNAUTHENTICATED_USER_IDENTIFIER ?
                new TestUserSession($currentUserIdentifier, $symfonyUserRoles, true) :
                new TestUserSession(null, [], false),
            new UserAttributeMuxer(new UserAttributeProviderProvider([$userAttributeProvider]), new EventDispatcher()));

        $authorizationService->__injectUserAttributeService($userAttributeService);
    }
}
