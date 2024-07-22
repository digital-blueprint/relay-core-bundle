<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;
use Symfony\Component\Uid\Uuid;

class TestDataProcessor extends AbstractDataProcessor
{
    private array $items = [];

    protected function addItem(mixed $data, array $filters): TestEntity
    {
        assert($data instanceof TestEntity);

        $identifier = (string) Uuid::v7();
        $data->setIdentifier($identifier);
        $this->items[$identifier] = $data;

        return $data;
    }

    protected function updateItem(mixed $identifier, mixed $data, mixed $previousData, array $filters): mixed
    {
        assert($data instanceof TestEntity);
        $this->items[$identifier] = $data;

        return $data;
    }

    protected function replaceItem(mixed $identifier, mixed $data, mixed $previousData, array $filters): mixed
    {
        assert($data instanceof TestEntity);
        $this->items[$identifier] = $data;

        return $data;
    }

    protected function removeItem(mixed $identifier, mixed $data, array $filters): void
    {
        assert($data instanceof TestEntity);

        unset($this->items[$identifier]);
    }

    public function getItemByIdentifier(string $identifier): ?TestEntity
    {
        return $this->items[$identifier] ?? null;
    }

    public function getItems(): array
    {
        return array_values($this->items);
    }
}
