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

    /**
     * Use this to set up the given data processor (i.e. inject all required services and set up a test user)
     * and create a new data provider tester instance for it.
     *
     * @param string   $resourceClass         the fully qualified class name of the entity that this data provider provides
     * @param string[] $denormalizationGroups the denormalization groups of the entity that this data provider provides
     */
    public static function create(AbstractDataProcessor $dataProcessor, string $resourceClass, array $denormalizationGroups = []): DataProcessorTester
    {
        self::setUp($dataProcessor);

        return new DataProcessorTester($dataProcessor, $resourceClass, $denormalizationGroups);
    }

    /**
     * Use this to set up the given data processor (i.e. inject all required services and set up a test user).
     */
    public static function setUp(AbstractDataProcessor $dataProcessor,
        string $currentUserIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER, array $currentUserAttributes = []): void
    {
        TestAuthorizationService::setUp($dataProcessor, $currentUserIdentifier, $currentUserAttributes);
    }

    private function __construct(AbstractDataProcessor $dataProcessor, string $resourceClass, array $denormalizationGroups = [])
    {
        $this->dataProcessor = $dataProcessor;
        $this->resourceClass = $resourceClass;
        $this->denormalizationGroups = $denormalizationGroups;
    }

    public function addItem(mixed $data, array $filters = []): mixed
    {
        return $this->dataProcessor->process($data, new Post(), [], $this->createContext($filters));
    }

    public function replaceItem(mixed $identifier, mixed $data, mixed $previousData, array $filters = []): mixed
    {
        return $this->dataProcessor->process($data, new Put(), [self::IDENTIFIER_NAME => $identifier],
            $this->createContext($filters, $previousData));
    }

    public function updateItem(mixed $identifier, mixed $data, mixed $previousData, array $filters = []): mixed
    {
        return $this->dataProcessor->process($data, new Patch(), [self::IDENTIFIER_NAME => $identifier],
            $this->createContext($filters, $previousData));
    }

    public function removeItem(mixed $identifier, mixed $data, array $filters = []): void
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
