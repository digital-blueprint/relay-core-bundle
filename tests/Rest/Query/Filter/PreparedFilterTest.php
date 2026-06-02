<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Rest\Query\Filter\PreparedFilters;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthorizationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PreparedFilterTest extends TestCase
{
    private const DEFAULT_USER_ATTRIBUTES = [
        'IS_USER' => false,
        'IS_VIEWER' => false,
        'IS_ADMIN' => false,
    ];
    private ?PreparedFilters $preparedFilterProvider = null;

    private ?array $config = null;
    private ?TestAuthorizationService $authoriationService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = ['prepared_filters' => [
            [
                'id' => 'filter0',
                'filter' => 'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]="value0"',
                'use_policy' => 'user.get("IS_USER")',
                'force_use_policy' => 'true',
            ],
            [
                'id' => 'filterShortcut',
                'filter' => 'filter[field0]="value0"',
                'use_policy' => 'true',
                'force_use_policy' => 'user.get("IS_VIEWER")',
            ],
            [
                'id' => 'filterBackendOnly',
                'filter' => 'filter[field0]="value0"',
                'use_policy' => null,
                'force_use_policy' => 'user.getIdentifier() === "my-client-id"',
            ],
            [
                'id' => 'filterFrontendOnly',
                'use_policy' => 'user.get("IS_USER")',
                'force_use_policy' => null,
            ],
            [
                'id' => 'filterWithForceUseForUsers',
                'filter' => 'filter[field0]="value0"',
                'use_policy' => null,
                'force_use_policy' => null,
                'force_use_for_users' => ['my-client-id', 'other-client-id'],
            ],
        ]];

        $this->preparedFilterProvider = new PreparedFilters();
        $this->preparedFilterProvider->loadConfig($this->config);
        $this->authoriationService = new TestAuthorizationService();

        $this->login();
    }

    public function testAssertCurrentUserMayUseFilterWithPolicy(): void
    {
        try {
            $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filter0', $this->authoriationService);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $httpException) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $httpException->getStatusCode());
        }
        $userAttributes = self::DEFAULT_USER_ATTRIBUTES;
        $userAttributes['IS_USER'] = true;
        $this->login(userAttributes: $userAttributes);
        $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filter0', $this->authoriationService);
        $this->assertTrue(true);
    }

    public function testAssertCurrentUserMayUseFilterWithPolicyTrue(): void
    {
        $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filterShortcut', $this->authoriationService);
        $this->assertTrue(true);
    }

    public function testAssertCurrentUserMayUseFilterBackendOnly(): void
    {
        try {
            $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filterBackendOnly', $this->authoriationService);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $httpException) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $httpException->getStatusCode());
        }
    }

    public function testAssertCurrentUserMayUseFilterFrontendOnly(): void
    {
        try {
            $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filter0', $this->authoriationService);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $httpException) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $httpException->getStatusCode());
        }
        $userAttributes = self::DEFAULT_USER_ATTRIBUTES;
        $userAttributes['IS_USER'] = true;
        $this->login(userAttributes: $userAttributes);
        $this->preparedFilterProvider->assertCurrentUserMayUseFilter('filter0', $this->authoriationService);
        $this->assertTrue(true);
    }

    public function testGetFiltersToForceUseForCurrentUser(): void
    {
        $filtersToForce = $this->preparedFilterProvider->getFiltersToForceUseForCurrentUser($this->authoriationService);
        $this->assertEquals(['filter0'], $filtersToForce);

        $userAttributes = self::DEFAULT_USER_ATTRIBUTES;
        $userAttributes['IS_VIEWER'] = true;
        $this->login(userAttributes: $userAttributes);
        $filtersToForce = $this->preparedFilterProvider->getFiltersToForceUseForCurrentUser($this->authoriationService);
        $this->assertCount(2, $filtersToForce);
        $this->assertContains('filter0', $filtersToForce);
        $this->assertContains('filterShortcut', $filtersToForce);

        $userAttributes = self::DEFAULT_USER_ATTRIBUTES;
        $userAttributes['IS_VIEWER'] = true;
        $this->login(userIdentifier: 'my-client-id', userAttributes: $userAttributes);
        $filtersToForce = $this->preparedFilterProvider->getFiltersToForceUseForCurrentUser($this->authoriationService);
        $this->assertCount(4, $filtersToForce);
        $this->assertContains('filter0', $filtersToForce);
        $this->assertContains('filterShortcut', $filtersToForce);
        $this->assertContains('filterBackendOnly', $filtersToForce);
        $this->assertContains('filterWithForceUseForUsers', $filtersToForce);

        $this->login(userIdentifier: 'other-client-id');
        $filtersToForce = $this->preparedFilterProvider->getFiltersToForceUseForCurrentUser($this->authoriationService);
        $this->assertCount(2, $filtersToForce);
        $this->assertContains('filter0', $filtersToForce);
        $this->assertContains('filterWithForceUseForUsers', $filtersToForce);
    }

    /**
     * @throws \Exception
     */
    public function testGetPreparedFilterQueryString(): void
    {
        $this->assertEquals(
            'filter[foo][condition][path]=field0&filter[foo][condition][operator]=I_CONTAINS&filter[foo][condition][value]="value0"',
            $this->preparedFilterProvider->getPreparedFilterQueryString('filter0'));
        $this->assertEquals(
            '',
            $this->preparedFilterProvider->getPreparedFilterQueryString('filterFrontendOnly')
        );
        $this->expectException(\RuntimeException::class);
        $this->preparedFilterProvider->getPreparedFilterQueryString('___');
    }

    private function login(
        ?string $userIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER,
        array $userAttributes = self::DEFAULT_USER_ATTRIBUTES): void
    {
        TestAuthorizationService::setUp($this->authoriationService, $userIdentifier, $userAttributes);
    }
}
