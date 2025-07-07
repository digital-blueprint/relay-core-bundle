<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;

/**
 * @extends AbstractDataProvider<TestResource>
 */
class TestResourceProvider extends AbstractDataProvider
{
    public function __construct(
        private readonly TestResourceService $testResourceService,
        private readonly TestApiAuthorizationService $authorizationService)
    {
        parent::__construct();
    }

    protected function getItemById(string $id, array $filters = [], array $options = []): ?object
    {
        // 'foobar' is a predefined test resource
        if ($id === 'foobar') {
            // override access control policies since used user attributes (e.g. 'IS_ADMIN') might not be set up in the
            // test and would otherwise cause a user attribute not found exception
            $this->authorizationService->setUpAccessControlPolicies([
                'ROLE_ADMIN' => 'false',
            ], [
                'READ_TEST_RESOURCE' => 'false',
            ]);

            $instance = new TestResource();
            $instance->setIdentifier($id);
            $instance->setContent('content');
            $instance->setSecret('secret');

            return $instance;
        }

        $testResource = $this->testResourceService->getTestResource($id, $filters, $options);
        if ($filters['debug'] ?? false) {
            var_dump($testResource);
        }

        return $testResource;
    }

    /**
     * @throws \Exception
     */
    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return $this->testResourceService->getTestResources($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
    }
}
