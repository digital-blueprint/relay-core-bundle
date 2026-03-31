<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\TestResourceEntityManager;
use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;
use Dbp\Relay\CoreBundle\TestUtils\TestClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiTestCase extends AbstractApiTest
{
    protected const TEST_CONTENT = TestResourceEntityManager::CONTENT_DEFAULT;
    protected const TEST_SECRET = TestResourceEntityManager::SECRET_DEFAULT;
    protected const TEST_IS_PUBLIC = TestResourceEntityManager::IS_PUBLIC_DEFAULT;
    protected const TEST_PASSWORD = TestResourceEntityManager::PASSWORD_DEFAULT;

    protected const USER_ATTRIBUTE_DEFAULT_VALUES = [
        'IS_ADMIN' => false,
        'FORCE_USE_PREPARED_FILTER' => false,
    ];

    protected ?TestResourceEntityManager $testResourceManager = null;

    protected function setUp(): void
    {
        $this->setUpTestClient();

        $this->testResourceManager = new TestResourceEntityManager($this->testClient->getContainer());
    }

    protected function getTestClient(
        string $userIdentifier = TestClient::TEST_USER_IDENTIFIER,
        array $userAttributes = self::USER_ATTRIBUTE_DEFAULT_VALUES): TestClient
    {
        $this->login($userIdentifier, $userAttributes);

        return $this->testClient;
    }

    protected function addTestResource(string $content = self::TEST_CONTENT, bool $isPublic = self::TEST_IS_PUBLIC,
        string $secret = self::TEST_SECRET): TestResource
    {
        return $this->testResourceManager->addTestResource($content, $isPublic, $secret);
    }

    protected function addTestSubResource(TestResource $testResource, bool $isPublic = self::TEST_IS_PUBLIC,
        string $password = self::TEST_PASSWORD): TestSubResource
    {
        return $this->testResourceManager->addTestSubResource($testResource, $isPublic, $password);
    }

    protected function getTestResourceEntityData(ResponseInterface $response): array
    {
        if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
            // var_dump($response->getContent(false));
        }
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED]);

        return json_decode($response->getContent(false), true);
    }

    protected function getTestResourceCollectionData(ResponseInterface $response): array
    {
        if ($response->getStatusCode() !== 200) {
            var_dump($response->getContent(false));
        }
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        return json_decode($response->getContent(false), true)['hydra:member'];
    }
}
