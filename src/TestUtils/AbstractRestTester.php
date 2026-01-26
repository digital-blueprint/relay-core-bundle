<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Symfony\Component\HttpFoundation\Request;

class AbstractRestTester
{
    protected const DEFAULT_IDENTIFIER_NAME = 'identifier';

    /**
     * Use this to set up a test user with the given user attributes.
     */
    public static function login(AbstractAuthorizationService $authorizationService,
        string $currentUserIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER, array $currentUserAttributes = []): void
    {
        TestAuthorizationService::setUp($authorizationService, $currentUserIdentifier, $currentUserAttributes);
    }

    public static function logout(AbstractAuthorizationService $authorizationService, array $userAttributeDefaults = []): void
    {
        TestAuthorizationService::setUp($authorizationService, currentUserAttributes: $userAttributeDefaults, isAuthenticated: false);
    }

    protected function __construct(
        protected readonly string $resourceClass,
        protected readonly array $normalizationGroups = [],
        protected readonly array $denormalizationGroups = [],
        protected readonly string $identifierName = self::DEFAULT_IDENTIFIER_NAME,
        protected readonly ?string $entityShortName = null)
    {
    }

    protected function createContext(string $method, ?string $identifier = null, array $filters = [], mixed $previousData = null): array
    {
        $request = self::createRequest(
            uri: '/test/test-entities'.($identifier ? '/'.$identifier : ''),
            method: $method);
        $request->query->replace($filters);
        $groups = $method === 'GET' ? $this->normalizationGroups : $this->denormalizationGroups;

        return [
            'filters' => $filters,
            'resource_class' => $this->resourceClass,
            'groups' => $groups,
            'previous_data' => $previousData,
            'request' => $request,
        ];
    }

    protected function createRequest(string $uri, string $method,
        array $queryParameters = [], array $requestParameters = [], array $files = []): Request
    {
        $request = Request::create($uri, $method);
        $request->query->replace($queryParameters);
        $request->request->replace($requestParameters);
        $request->files->replace($files);

        return $request;
    }
}
