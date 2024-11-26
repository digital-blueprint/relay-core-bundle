<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProvider;
use Dbp\Relay\CoreBundle\Tests\TestApi\Authorization\TestApiAuthorizationService;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;

/**
 * @extends AbstractDataProvider<TestSubResource>
 */
class TestSubResourceProvider extends AbstractDataProvider
{
    public function __construct(
        private readonly TestResourceService $testResourceService,
        private readonly TestApiAuthorizationService $authorizationService)
    {
        parent::__construct();
    }

    protected function getItemById(string $id, array $filters = [], array $options = []): ?TestSubResource
    {
        $testSubResource = $this->testResourceService->getTestSubResource($id);
        if ($filters['debug'] ?? false) {
            dump($testSubResource);
        }

        return $testSubResource;
    }

    protected function getPage(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = [], array $options = []): array
    {
        return $this->testResourceService->getTestSubResources($currentPageNumber, $maxNumItemsPerPage, $filters, $options);
    }
}
