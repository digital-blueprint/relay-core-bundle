<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected ?ApiTestClient $testClient = null;

    protected function createTestClient(array $kernelOptions = []): void
    {
        self::ensureKernelShutdown();
        $this->testClient = new ApiTestClient(self::createClient($kernelOptions));
        $this->testClient->getKernelBrowser()->disableReboot(); // allow multiple requests in one test
    }

    /**
     * Logs in a user for subsequent requests.
     *
     * @param array<string, mixed> $userAttributes User attributes used for authorization
     * @param string|null          $token          The bearer token used to authenticate subsequent
     *                                             requests, or null for unauthenticated requests
     */
    protected function login(
        string $userIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER,
        array $userAttributes = [],
        ?string $token = ApiTestClient::TEST_TOKEN): void
    {
        if ($this->testClient === null) {
            $this->createTestClient();
        }
        assert($this->testClient !== null);

        $this->testClient->setUpUser(
            $userIdentifier,
            userAttributes: $userAttributes,
            token: $token,
        );
    }
}
