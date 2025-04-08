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
    protected const IS_ADMIN_USER_ATTRIBUTE = 'IS_ADMIN';
    protected const IS_USER_USER_ATTRIBUTE = 'IS_USER';

    public function testGetUserIdentifier()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertEquals(TestAuthorizationService::TEST_USER_IDENTIFIER, $authorizationService->getUserIdentifier());
    }

    public function testIsAuthenticated()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isAuthenticated());

        $authorizationService = $this->getTestAuthorizationService(isAuthenticated: false);
        $this->assertFalse($authorizationService->isAuthenticated());
    }

    /**
     * @throws UserAttributeException
     */
    public function testGetUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->getUserAttribute(self::IS_USER_USER_ATTRIBUTE));
        $this->assertFalse($authorizationService->getUserAttribute(self::IS_ADMIN_USER_ATTRIBUTE));
        $this->assertEquals('test@example.com', $authorizationService->getUserAttribute('EMAIL'));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertFalse($authorizationService->getUserAttribute(self::IS_USER_USER_ATTRIBUTE));
        $this->assertTrue($authorizationService->getUserAttribute(self::IS_ADMIN_USER_ATTRIBUTE));
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

    public function testIsGrantedRole()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGrantedRole('MAY_USE'));
        $this->assertFalse($authorizationService->isGrantedRole('MAY_MANAGE'));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGrantedRole('MAY_USE'));
        $this->assertTrue($authorizationService->isGrantedRole('MAY_MANAGE'));
    }

    public function testIsGrantedResourcePermission()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGrantedResourcePermission('MAY_ACCESS', new TestEntity('public')));
        $this->assertFalse($authorizationService->isGrantedResourcePermission('MAY_ACCESS', new TestEntity('private')));

        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::ADMIN_USER_IDENTIFIER);
        $this->assertTrue($authorizationService->isGrantedResourcePermission('MAY_ACCESS', new TestEntity('public')));
        $this->assertTrue($authorizationService->isGrantedResourcePermission('MAY_ACCESS', new TestEntity('private')));
    }

    public function testIsGrantedUndefinedRole()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedRole('undefined');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::ROLE_UNDEFINED, $authorizationException->getCode());
        }
    }

    public function testIsGrantedUndefinedResourcePermission()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedResourcePermission('undefined', new TestEntity());
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::RESOURCE_PERMISSION_UNDEFINED, $authorizationException->getCode());
        }
    }

    public function testGetAttributeInfiniteRoleLoop()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedRole('INFINITE_EXPRESSION');
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED, $authorizationException->getCode());
        }
    }

    public function testGetAttributeInfiniteResourcePermissionLoop()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedResourcePermission('INFINITE_EXPRESSION', new TestEntity());
            $this->fail('exception not thrown as expected');
        } catch (AuthorizationException $authorizationException) {
            $this->assertEquals(AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED, $authorizationException->getCode());
        }
    }

    public function testIsGrantedRoleWithUndefinedUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedRole('USER_ATTRIBUTE_UNDEFINED');
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $userAttributeException) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $userAttributeException->getCode());
        }
    }

    public function testIsGrantedResourcePermissionWithUndefinedUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->isGrantedResourcePermission('USER_ATTRIBUTE_UNDEFINED', new TestEntity());
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $userAttributeException) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $userAttributeException->getCode());
        }
    }

    public function testDeprecateDenyAccessUnlessGranted()
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

    public function testDenyAccessUnlessGrantedRole()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $authorizationService->denyAccessUnlessIsGrantedRole('MAY_USE');

        try {
            $authorizationService->denyAccessUnlessIsGrantedRole('MAY_MANAGE');
            $this->fail('exception not thrown as expected');
        } catch (ApiError $apiError) {
            $this->assertEquals(403, $apiError->getStatusCode());
        }
    }

    public function testDenyAccessUnlessGrantedResourcePermission()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $authorizationService->denyAccessUnlessIsGrantedResourcePermission('MAY_ACCESS', new TestEntity('public'));

        try {
            $authorizationService->denyAccessUnlessIsGrantedResourcePermission('MAY_ACCESS', new TestEntity('private'));
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
            $this->assertEquals(AuthorizationException::INFINITE_EXPRESSION_LOOP_DETECTED, $authorizationException->getCode());
        }
    }

    public function testGetAttributedWithUndefinedUserAttribute()
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        try {
            $authorizationService->getAttribute('USER_ATTRIBUTE_UNDEFINED');
            $this->fail('exception not thrown as expected');
        } catch (UserAttributeException $userAttributeException) {
            $this->assertEquals(UserAttributeException::USER_ATTRIBUTE_UNDEFINED, $userAttributeException->getCode());
        }
    }

    public function testDeprecateIsPolicyDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isPolicyDefined('MAY_ACCESS'));
        $this->assertFalse($authorizationService->isPolicyDefined('404'));
    }

    public function testIsRoleDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isRoleDefined('MAY_USE'));
        $this->assertFalse($authorizationService->isRoleDefined('404'));
    }

    public function testIsResourcePermissionDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isResourcePermissionDefined('MAY_ACCESS'));
        $this->assertFalse($authorizationService->isResourcePermissionDefined('404'));
    }

    public function testIsAttributeDefined(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);

        $this->assertTrue($authorizationService->isAttributeDefined('NULL_ATTRIBUTE'));
        $this->assertFalse($authorizationService->isAttributeDefined('404'));
    }

    public function testDeprecateGetPolicyNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $policyNames = $authorizationService->getPolicyNames();
        $this->assertCount(3, $policyNames);
        $this->assertEquals('MAY_ACCESS', $policyNames[0]);
        $this->assertEquals('INFINITE_EXPRESSION', $policyNames[1]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED', $policyNames[2]);
    }

    public function testGetRoleNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $roleNames = $authorizationService->getRoleNames();
        $this->assertCount(4, $roleNames);
        $this->assertEquals('MAY_USE', $roleNames[0]);
        $this->assertEquals('MAY_MANAGE', $roleNames[1]);
        $this->assertEquals('INFINITE_EXPRESSION', $roleNames[2]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED', $roleNames[3]);
    }

    public function testGetResourcePermissionNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $policyNames = $authorizationService->getResourcePermissionNames();
        $this->assertCount(3, $policyNames);
        $this->assertEquals('MAY_ACCESS', $policyNames[0]);
        $this->assertEquals('INFINITE_EXPRESSION', $policyNames[1]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED', $policyNames[2]);
    }

    public function testGetAttributeNames(): void
    {
        $authorizationService = $this->getTestAuthorizationService(TestAuthorizationService::TEST_USER_IDENTIFIER);
        $attributeNames = $authorizationService->getAttributeNames();
        $this->assertCount(4, $attributeNames);
        $this->assertEquals('MY_ORG_IDS', $attributeNames[0]);
        $this->assertEquals('NULL_ATTRIBUTE', $attributeNames[1]);
        $this->assertEquals('INFINITE_ATTRIBUTE', $attributeNames[2]);
        $this->assertEquals('USER_ATTRIBUTE_UNDEFINED', $attributeNames[3]);
    }

    private function getTestAuthorizationService(?string $userIdentifier = null, bool $isAuthenticated = true): TestAuthorizationService
    {
        $authorizationService = TestAuthorizationService::create($userIdentifier, [
            self::IS_USER_USER_ATTRIBUTE => $userIdentifier === TestAuthorizationService::TEST_USER_IDENTIFIER,
            self::IS_ADMIN_USER_ATTRIBUTE => $userIdentifier === TestAuthorizationService::ADMIN_USER_IDENTIFIER,
            'EMAIL' => 'test@example.com',
            'NULL' => null,
        ], isAuthenticated: $isAuthenticated);
        $authorizationService->setUpAccessControlPolicies([
            'MAY_USE' => 'user.get("'.self::IS_USER_USER_ATTRIBUTE.'") || user.get("'.self::IS_ADMIN_USER_ATTRIBUTE.'")',
            'MAY_MANAGE' => 'user.get("'.self::IS_ADMIN_USER_ATTRIBUTE.'")',
            'INFINITE_EXPRESSION' => 'user.isGranted("INFINITE_EXPRESSION")',
            'USER_ATTRIBUTE_UNDEFINED' => 'user.get("undefined")',
        ], [
            'MAY_ACCESS' => 'user.get("'.self::IS_ADMIN_USER_ATTRIBUTE.'") || resource.getIdentifier() === "public"',
            'INFINITE_EXPRESSION' => 'user.isGranted("INFINITE_EXPRESSION")',
            'USER_ATTRIBUTE_UNDEFINED' => 'user.get("undefined")',
        ], [
            'MY_ORG_IDS' => 'Relay.ternaryOperator(user.get("'.self::IS_ADMIN_USER_ATTRIBUTE.'"), [1, 2, 3], [1])',
            'NULL_ATTRIBUTE' => 'null',
            'INFINITE_ATTRIBUTE' => 'user.getAttribute("INFINITE_ATTRIBUTE")',
            'USER_ATTRIBUTE_UNDEFINED' => 'user.get("undefined")',
        ]);

        return $authorizationService;
    }
}
