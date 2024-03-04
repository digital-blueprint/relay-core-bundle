<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;

class DataProcessorTester
{
    private const IDENTIFIER_NAME = 'identifier';

    private AbstractDataProcessor $dataProcessor;
    private string $resourceClass;
    private array $denormalizationGroups;

    public static function setUp(AbstractDataProcessor $dataProcessor)
    {
        TestAuthorizationService::setUp($dataProcessor);
    }

    public function __construct(AbstractDataProcessor $dataProcessor, string $resourceClass, array $denormalizationGroups = [])
    {
        $this->dataProcessor = $dataProcessor;
        $this->resourceClass = $resourceClass;
        $this->denormalizationGroups = $denormalizationGroups;
    }

    public function addItem($data, array $filters = [])
    {
        return $this->dataProcessor->process($data, new Post(), [], $this->createContext($filters));
    }

    public function replaceItem($identifier, $data, $previousData, array $filters = [])
    {
        return $this->dataProcessor->process($data, new Put(), [self::IDENTIFIER_NAME => $identifier],
            $this->createContext($filters, $previousData));
    }

    public function updateItem($identifier, $data, $previousData, array $filters = [])
    {
        return $this->dataProcessor->process($data, new Patch(), [self::IDENTIFIER_NAME => $identifier],
            $this->createContext($filters, $previousData));
    }

    public function removeItem($identifier, $data, array $filters = []): void
    {
        $this->dataProcessor->process($data, new Delete(), [self::IDENTIFIER_NAME => $identifier],
            $this->createContext($filters));
    }

    private function createContext(array $filters = [], $previousData = null): array
    {
        return [
            'filters' => $filters,
            'resource_class' => $this->resourceClass,
            'groups' => $this->denormalizationGroups,
            'previous_data' => $previousData,
        ];
    }
}
