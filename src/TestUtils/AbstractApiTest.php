<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

abstract class AbstractApiTest extends ApiTestCase
{
    protected ?TestClient $testClient = null;

    /**
     * WORKAROUND deprecation warning.
     */
    public static function setUpBeforeClass(): void
    {
        $reflection = new \ReflectionClass(ApiTestCase::class);
        if ($reflection->hasProperty('alwaysBootKernel')) {
            static::$alwaysBootKernel = true;
        }
    }

    protected function setUp(): void
    {
        $this->testClient = new TestClient(self::createClient());
        $this->testClient->getClient()->disableReboot(); // allow multiple requests in one test
        $this->login();
    }

    protected function login(
        string $userIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER,
        array $userAttributes = []): void
    {
        $this->testClient->setUpUser($userIdentifier, userAttributes: $userAttributes);
    }
}
