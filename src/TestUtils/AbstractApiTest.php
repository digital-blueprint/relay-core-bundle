<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractApiTest extends ApiTestCase
{
    protected ?TestClient $testClient = null;

    /**
     * WORKAROUND deprecation warning on self::createClient().
     */
    public static function setUpBeforeClass(): void
    {
        $reflection = new \ReflectionClass(ApiTestCase::class);
        if ($reflection->hasProperty('alwaysBootKernel')) {
            static::$alwaysBootKernel = true; // @phpstan-ignore-line
        }
    }

    protected function setUp(): void
    {
        $this->setUpTestClient();
    }

    protected function setUpTestClient(array $kernelOptions = []): void
    {
        KernelTestCase::ensureKernelShutdown();
        $this->testClient = new TestClient(self::createClient($kernelOptions));
        $this->testClient->getClient()->disableReboot(); // allow multiple requests in one test
        $this->login();
    }

    protected function login(
        string $userIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER,
        ?array $userAttributes = null): void
    {
        $this->testClient->setUpUser($userIdentifier, userAttributes: $userAttributes ?? $this->getUserAttributeDefaultValues());
    }

    /**
     * Override to define the user attribute default values.
     *
     * @return array<string, mixed> A mapping from user attribute name to default value
     */
    protected function getUserAttributeDefaultValues(): array
    {
        return [];
    }
}
