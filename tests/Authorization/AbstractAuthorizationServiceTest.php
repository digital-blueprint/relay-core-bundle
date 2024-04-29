<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationException;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Tests\Rest\TestEntity;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthorizationService;
use Dbp\Relay\CoreBundle\User\UserAttributeException;
use PHPUnit\Framework\TestCase;

class AbstractAuthorizationServiceTest extends TestCase
{
    public function testGetUserIdentifier()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals(TestAuthorizationService::TEST_USER_IDENTIFIER, $authorizationService->getUserIdentifier());
    }

    public function testIsAuthenticated()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isAuthenticated());

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::UNAUTHENTICATED_USER_IDENTIFIER);
        $this->assertFalse($authorizationService->isAuthenticated());
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals(true, $authorizationService->getUserAttribute('ROLE_USER'));
        $this->assertEquals(false, $authorizationService->getUserAttribute('ROLE_ADMIN'));
        $this->assertEquals('test@example.com', $authorizationService->getUserAttribute('EMAIL'));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertEquals(false, $authorizationService->getUserAttribute('ROLE_USER'));
        $this->assertEquals(true, $authorizationService->getUserAttribute('ROLE_ADMIN'));
        $this->assertEquals('test@example.com', $authorizationService->getUserAttribute('EMAIL'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetUserAttributeDefaultValue()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals('default', $authorizationService->getUserAttribute('NULL', 'default'));
    }

    public function testUserAttributeException()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->getUserAttribute('undefined');
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $exception) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $exception->getCode());
        }
    }

    public function testIsGrantedWithoutResource()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGranted('MAY_USE'));
        $this->assertFalse($authorizationService->isGranted('MAY_MANAGE'));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGranted('MAY_USE'));
        $this->assertTrue($authorizationService->isGranted('MAY_MANAGE'));
    }

    public function testIsGrantedWithResource()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGranted('MAY_ACCESS', new TestEntity('public')));
        $this->assertFalse($authorizationService->isGranted('MAY_ACCESS', new TestEntity('private')));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGranted('MAY_ACCESS', new TestEntity('public')));
        $this->assertTrue($authorizationService->isGranted('MAY_ACCESS', new TestEntity('private')));
    }

    public function testIsGrantedWithResourceAlias()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGranted('MAY_ACCESS_RESOURCE_ALIAS', new TestEntity('public'), 'testEntity'));
    }

    public function testIsGrantedUndefinedPolicy()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGranted('undefined');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::POLICY_UNDEFINED, $authorizationException->getCode());
        }
    }

    public function testGetAttributeInfinitePolicyLoop()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGranted('INFINITE_POLICY');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED, $authorizationException->getCode());
        }
    }

    public function testIsGrantedWithUndefinedUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGranted('USER_ATTRIBUTE_UNDEFINED_POLICY');
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $userAttributeException) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $userAttributeException->getCode());
        }
    }

    public function testDenyAccessUnlessGrantedWithoutResource()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $authorizationService->denyAccessUnlessIsGranted('MAY_USE');

        try {
            $authorizationService->denyAccessUnlessIsGranted('MAY_MANAGE');
            $this->fail('exception not thrown as expected');
        } catch (ApiError $apiError) {
            $this->assertEquals(403, $apiError->getStatusCode());
        }
    }

    public function testDenyAccessUnlessGrantedWithResource()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $authorizationService->denyAccessUnlessIsGranted('MAY_ACCESS', new TestEntity('public'));

        try {
            $authorizationService->denyAccessUnlessIsGranted('MAY_ACCESS', new TestEntity('private'));
            $this->fail('exception not thrown as expected');
        } catch (ApiError $apiError) {
            $this->assertEquals(403, $apiError->getStatusCode());
        }
    }

    public function testGetAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals([1], $authorizationService->getAttribute('MY_ORG_IDS'));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertEquals([1, 2, 3], $authorizationService->getAttribute('MY_ORG_IDS'));
    }

    public function testGetAttributeDefaultValue()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals(42, $authorizationService->getAttribute('NULL_ATTRIBUTE', 42));
    }

    public function testGetAttributeUndefinedAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->getAttribute('undefined');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::ATTRIBUTE_UNDEFINED, $authorizationException->getCode());
        }
    }

    public function testGetAttributeInfiniteAttributeLoop()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->getAttribute('INFINITE_ATTRIBUTE');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::INFINITE_EXRPESSION_LOOP_DETECTED, $authorizationException->getCode());
        }
    }

    public function testGetAttributedWithUndefinedUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->getAttribute('USER_ATTRIBUTE_UNDEFINED_ATTRIBUTE');
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $userAttributeException) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $userAttributeException->getCode());
        }
    }

    public function testIsPolicyDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isPolicyDefined('MAY_USE'));
        $this->assertFalse($authorizationService->isPolicyDefined('404'));
    }

    public function testIsAttributeDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isAttributeDefined('NULL_ATTRIBUTE'));
        $this->assertFalse($authorizationService->isAttributeDefined('404'));
    }

    public function testGetPolicyNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $policyNames = $authorizationService->getPolicyNames();
        $this->assertCount(6, $policyNames);
        $this->assertEquals('MAY_USE', $policyNames[0]);
        $this->assertEquals('MAY_MANAGE', $policyNames[1]);
        $this->assertEquals('MAY_ACCESS', $policyNames[2]);
        $this->assertEquals('MAY_ACCESS_RESOURCE_ALIAS', $policyNames[3]);
        $this->assertEquals('INFINITE_POLICY', $policyNames[4]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED_POLICY', $policyNames[5]);
    }

    public function testGetAttributeNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $attributeNames = $authorizationService->getAttributeNames();
        $this->assertCount(4, $attributeNames);
        $this->assertEquals('MY_ORG_IDS', $attributeNames[0]);
        $this->assertEquals('NULL_ATTRIBUTE', $attributeNames[1]);
        $this->assertEquals('INFINITE_ATTRIBUTE', $attributeNames[2]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED_ATTRIBUTE', $attributeNames[3]);
    }

    private function getTestAuthorizationService(string $userIdentifier): TestAuthorizationService
    {
        $authorizationService = TestAuthorizationService::create($userIdentifier, [
            'ROLE_USER' => $userIdentifier === TestAuthorizationService::TEST_USER_IDENTIFIER,
            'ROLE_ADMIN' => $userIdentifier === TestAuthorizationService::ADMIN_USER_IDENTIFIER,
            'EMAIL' => 'test@example.com',
            'NULL' => null,
        ]);
        $authorizationService->configure([
            'MAY_USE' => 'user.get("ROLE_USER") || user.get("ROLE_ADMIN")',
            'MAY_MANAGE' => 'user.get("ROLE_ADMIN")',
            'MAY_ACCESS' => 'user.get("ROLE_ADMIN") || resource.getIdentifier() !== "private"',
            'MAY_ACCESS_RESOURCE_ALIAS' => 'user.get("ROLE_ADMIN") || testEntity.getIdentifier() !== "private"',
            'INFINITE_POLICY' => 'user.isGranted("INFINITE_POLICY")',
            'USER_ATTRIBUTE_UNDEFINED_POLICY' => 'user.get("undefined")',
        ], [
            'MY_ORG_IDS' => 'Relay.ternaryOperator(user.get("ROLE_ADMIN"), [1, 2, 3], [1])',
            'NULL_ATTRIBUTE' => 'null',
            'INFINITE_ATTRIBUTE' => 'user.getAttribute("INFINITE_ATTRIBUTE")',
            'USER_ATTRIBUTE_UNDEFINED_ATTRIBUTE' => 'user.get("undefined")',
        ]);

        return $authorizationService;
    }
}
