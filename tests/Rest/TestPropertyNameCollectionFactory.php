<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;

class TestPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];

        if ($resourceClass === TestEntity::class) {
            $groups = $options['serializer_groups'] ?? [];
            if (in_array('TestEntity:output', $groups, true)) {
                $propertyNames[] = 'identifier';
                $propertyNames[] = 'field0';
            }
            if (in_array('LocalData:output', $groups, true)) {
                $propertyNames[] = 'localData';
            }
        }

        return new PropertyNameCollection($propertyNames);
    }
}
