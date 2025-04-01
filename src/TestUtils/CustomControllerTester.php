<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Rest\CustomControllerTrait;
use Symfony\Component\HttpFoundation\Request;

class CustomControllerTester extends AbstractRestTester
{
    private \Closure $testItemOperationCallback;
    private \Closure $testCollectionOperationCallback;

    /**
     * Use this to set up the given data processor (i.e. inject all required services and set up a test user)
     * and create a new data provider tester instance for it.
     *
     * @param string   $resourceClass         the fully qualified class name of the entity that this data provider provides
     * @param string[] $denormalizationGroups the denormalization groups of the entity that this data provider provides
     */
    public static function create(object $abstractController, string $resourceClass, string $uri = '/test/test-entities',
        array $normalizationGroups = [], array $denormalizationGroups = []): CustomControllerTester
    {
        if ($abstractController instanceof AbstractAuthorizationService) {
            self::login($abstractController);
        }
        if (in_array(CustomControllerTrait::class, class_uses($abstractController), true)) {
            $abstractController->__injectUserSession(
                new TestUserSession(TestAuthorizationService::TEST_USER_IDENTIFIER, [], true));
        }

        return new CustomControllerTester($abstractController, $resourceClass, $uri,
            $normalizationGroups, $denormalizationGroups);
    }

    protected function __construct(
        private readonly object $controller,
        string $resourceClass,
        private readonly string $uri,
        array $normalizationGroups = [],
        array $denormalizationGroups = [],
        ?callable $testItemOperationCallback = null,
        ?callable $testCollectionOperationCallback = null)
    {
        parent::__construct($resourceClass, $normalizationGroups, $denormalizationGroups);

        $this->testItemOperationCallback = $testItemOperationCallback ??
            function (Request $request, string $identifier) {
                return $this->controller->__invoke($request, $identifier);
            };
        $this->testCollectionOperationCallback = $testCollectionOperationCallback ??
            function (Request $request) {
                return $this->controller->__invoke($request);
            };
    }

    public function performItemOperation(string $identifier, string $method,
        array $queryParameters = [], array $requestParameters = [], array $files = []): mixed
    {
        $request = self::createRequest($this->uri."/$identifier", $method,
            $queryParameters, $requestParameters, $files);

        return ($this->testItemOperationCallback)($request, $identifier);
    }

    public function performCollectionOperation(string $method,
        array $queryParameters = [], array $requestParameters = [], array $files = []): mixed
    {
        $request = self::createRequest($this->uri, $method,
            $queryParameters, $requestParameters, $files);

        return ($this->testCollectionOperationCallback)($request);
    }
}
