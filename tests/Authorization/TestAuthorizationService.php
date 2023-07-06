<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Dbp\Relay\CoreBundle\TestUtils\TestUserSession;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TestAuthorizationService extends AbstractAuthorizationService
{
    public static function create(): TestAuthorizationService
    {
        $testAuthorizationService = new TestAuthorizationService();
        $testAuthorizationService->__injectServices(
            new TestUserSession('testuser'),
            new AuthorizationDataMuxer(new AuthorizationDataProviderProvider([]), new EventDispatcher()));

        return $testAuthorizationService;
    }
}
