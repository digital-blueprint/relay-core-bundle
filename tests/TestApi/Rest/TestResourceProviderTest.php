<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceEntityManager;
use Dbp\Relay\CoreBundle\TestUtils\DataProviderTester;
use Dbp\Relay\CoreBundle\TestUtils\TestAuthorizationService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestResourceProviderTest extends WebTestCase
{
    private ?TestResourceEntityManager $testEntityManager = null;
    private ?DataProviderTester $dataProviderTester = null;
    private ?TestResourceProvider $testResourceProvider = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->testEntityManager = new TestResourceEntityManager($kernel->getContainer());

        $authorizationService = new TestApiAuthorizationService();
        TestAuthorizationService::setUp($authorizationService);

        $this->testResourceProvider = new TestResourceProvider(
            new TestResourceService($this->testEntityManager->getEntityManager()),
            $authorizationService);
        $this->testResourceProvider->setConfig(self::getTestResourceProviderConfig());
        $this->dataProviderTester = DataProviderTester::create($this->testResourceProvider,
            TestResource::class, ['TestResource:output']);

        $this->loginUser();
    }

    protected function loginUser(): void
    {
        $userAttributes = TestApiAuthorizationService::DEFAULT_USER_ATTRIBUTES;
        $userAttributes[TestApiAuthorizationService::IS_USER_USER_ATTRIBUTE] = true;
        DataProviderTester::login($this->testResourceProvider,
            TestAuthorizationService::TEST_USER_IDENTIFIER, $userAttributes);
    }

    protected function loginAdmin(): void
    {
        $userAttributes = TestApiAuthorizationService::DEFAULT_USER_ATTRIBUTES;
        $userAttributes[TestApiAuthorizationService::IS_ADMIN_USER_ATTRIBUTE] = true;
        DataProviderTester::login($this->testResourceProvider,
            TestAuthorizationService::ADMIN_USER_IDENTIFIER, $userAttributes);
    }

    private function logout(): void
    {
        DataProviderTester::logout($this->testResourceProvider,
            TestApiAuthorizationService::DEFAULT_USER_ATTRIBUTES);
    }

    public function testGetItem(): void
    {
        // test if forced filter 'publicOnly' is applied to non-admin users
        $testResource = TestResource::createTestResource();
        $testResource->setIsPublic(false);
        $this->testEntityManager->saveEntity($testResource);

        $this->assertNull($this->dataProviderTester->getItem($testResource->getIdentifier()));

        $this->loginAdmin();
        $testResourcePersistence = $this->dataProviderTester->getItem($testResource->getIdentifier());
        $this->assertEquals($testResource->getIdentifier(), $testResourcePersistence->getIdentifier());
    }

    public function testGetPage(): void
    {
        // test if forced filter 'publicOnly' is applied to non-admin users
        $testResourcePublic = TestResource::createTestResource();
        $testResourcePublic->setIsPublic(true);
        $this->testEntityManager->saveEntity($testResourcePublic);

        $testResourcePrivate = TestResource::createTestResource();
        $testResourcePrivate->setIsPublic(false);
        $this->testEntityManager->saveEntity($testResourcePrivate);

        $resultPage = $this->dataProviderTester->getCollection();
        $this->assertCount(1, $resultPage);

        $this->loginAdmin();
        $resultPage = $this->dataProviderTester->getCollection();
        $this->assertCount(2, $resultPage);
    }

    protected static function getTestResourceProviderConfig(): array
    {
        $config['rest']['query']['filter']['enable_query_filters'] = true;
        $config['rest']['query']['filter']['enable_prepared_filters'] = true;
        $config['rest']['query']['filter']['prepared_filters'] = [
            [
                'id' => 'publicOnly',
                'filter' => 'filter[isPublic]=1',
                'force_use_policy' => '!user.get("'.TestApiAuthorizationService::IS_ADMIN_USER_ATTRIBUTE.'")',
            ],
        ];

        return $config;
    }
}
