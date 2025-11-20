<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use Dbp\Relay\CoreBundle\User\UserAttributeService;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService
{
    public const TEST_USER_IDENTIFIER = 'testuser';
    public const ADMIN_USER_IDENTIFIER = 'admin';

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

    /**
     * Resets the internal state (e.g. request caches).
     * Should be called between requests when performing multiple requests in a single test case.
     */
    public static function reset(AbstractAuthorizationService $authorizationService): void
    {
        $authorizationService->reset();
    }
}
