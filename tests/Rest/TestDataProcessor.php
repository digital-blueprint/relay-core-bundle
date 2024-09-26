<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;
use Symfony\Component\Uid\Uuid;

class TestDataProcessor extends AbstractDataProcessor
{
    private array $items = [];
    private ?array $filters = null;

    protected function addItem(mixed $data, array $filters): TestEntity
    {
        assert($data instanceof TestEntity);

        $identifier = (string) Uuid::v4();
        $data->setIdentifier($identifier);
        $this->items[$identifier] = $data;
        $this->filters = $filters;

        return $data;
    }

    protected function updateItem(mixed $identifier, mixed $data, mixed $previousData, array $filters): mixed
    {
        assert($data instanceof TestEntity);
        $this->items[$identifier] = $data;
        $this->filters = $filters;

        return $data;
    }

    protected function replaceItem(mixed $identifier, mixed $data, mixed $previousData, array $filters): mixed
    {
        assert($data instanceof TestEntity);
        $this->items[$identifier] = $data;
        $this->filters = $filters;

        return $data;
    }

    protected function removeItem(mixed $identifier, mixed $data, array $filters): void
    {
        assert($data instanceof TestEntity);

        unset($this->items[$identifier]);
        $this->filters = $filters;
    }

    public function getItemByIdentifier(string $identifier): ?TestEntity
    {
        return $this->items[$identifier] ?? null;
    }

    public function getItems(): array
    {
        return array_values($this->items);
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
