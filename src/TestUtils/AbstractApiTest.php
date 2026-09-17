<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApiTest extends WebTestCase
{
    protected ?TestClient $testClient = null;

    protected function setUp(): void
    {
        $this->setUpTestClient();
    }

    protected function setUpTestClient(array $kernelOptions = []): void
    {
        self::ensureKernelShutdown();
        $this->testClient = new TestClient(self::createClient($kernelOptions));
        $this->testClient->getKernelBrowser()->disableReboot(); // allow multiple requests in one test
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
