<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;

class TestSubResourceProcessor extends AbstractDataProcessor
{
    public function __construct(private readonly TestResourceService $testResourceService)
    {
        parent::__construct();
    }

    protected function addItem(mixed $data, array $filters): TestSubResource
    {
        assert($data instanceof TestSubResource);

        return $this->testResourceService->addTestSubResource($data);
    }

    protected function removeItem(mixed $identifier, mixed $data, array $filters): void
    {
        $this->testResourceService->removeTestSubResource($identifier);
    }
}
