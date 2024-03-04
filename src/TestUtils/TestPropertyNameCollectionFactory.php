<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

class TestPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        return new PropertyNameCollection([]);
    }
}
