<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Dbp\Relay\CoreBundle\User\UserAttributeProviderProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService extends AbstractAuthorizationService
{
    public static function create(): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        $testAuthorizationService->__injectUserSessionAndUserAttributeMuxer(
            new TestUserSession('testuser'),
            new UserAttributeMuxer(new UserAttributeProviderProvider([]), new EventDispatcher()));

        return $testAuthorizationService;
    }
}
