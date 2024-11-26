<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;

class TestResourceProcessor extends AbstractDataProcessor
{
    public function __construct(private readonly TestResourceService $testResourceService)
    {
        parent::__construct();
    }

    protected function addItem(mixed $data, array $filters): TestResource
    {
        assert($data instanceof TestResource);

        return $this->testResourceService->addTestResource($data);
    }

    protected function removeItem(mixed $identifier, mixed $data, array $filters): void
    {
        $this->testResourceService->removeTestResource($identifier);
    }
}
