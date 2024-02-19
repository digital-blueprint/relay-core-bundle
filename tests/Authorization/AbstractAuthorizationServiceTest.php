<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\TestUtils\TestAuthorizationService;
use PHPUnit\Framework\TestCase;

class AbstractAuthorizationServiceTest extends TestCase
{
    public function testGetUserIdentifier()
    {
        $userIdentifier = 'testuser';
        $authorizationService = TestAuthorizationService::create($userIdentifier, [], [], true);
        $this->assertEquals($userIdentifier, $authorizationService->getUserIdentifier());
    }

    public function testIsAuthenticated()
    {
        $authorizationService = TestAuthorizationService::create('testuser', [], [], true);
        $this->assertTrue($authorizationService->isAuthenticated());

        $authorizationService = TestAuthorizationService::create('testuser', [], [], false);
        $this->assertFalse($authorizationService->isAuthenticated());
    }
}
