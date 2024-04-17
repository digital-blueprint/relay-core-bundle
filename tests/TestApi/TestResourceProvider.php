<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class TestResourceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $instance = new TestResource();
        $instance->setIdentifier($uriVariables['identifier']);
        $instance->setContent(null);

        return $instance;
    }
}
