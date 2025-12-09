<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Dbp\Relay\CoreBundle\Rest\AbstractDataProcessor;
use Symfony\Component\HttpFoundation\Request;

class DataProcessorTester extends AbstractRestTester
{
    /**
     * Use this to set up the given data processor (i.e. inject all required services and set up a test user)
     * and create a new data provider tester instance for it.
     *
     * @param string   $resourceClass         the fully qualified class name of the entity that this data provider provides
     * @param string[] $denormalizationGroups the denormalization groups of the entity that this data provider provides
     */
    public static function create(AbstractDataProcessor $dataProcessor, string $resourceClass,
        array $denormalizationGroups = [], string $identifierName = 'identifier'): DataProcessorTester
    {
        self::setUp($dataProcessor);

        return new DataProcessorTester($dataProcessor, $resourceClass, $denormalizationGroups, $identifierName);
    }

    /**
     * Use this to set up the given data processor (i.e. inject all required services and set up a test user).
     */
    public static function setUp(AbstractDataProcessor $dataProcessor,
        string $currentUserIdentifier = TestAuthorizationService::TEST_USER_IDENTIFIER, array $currentUserAttributes = []): void
    {
        self::login($dataProcessor, $currentUserIdentifier, $currentUserAttributes);
    }

    private function __construct(
        private readonly AbstractDataProcessor $dataProcessor, string $resourceClass,
        array $denormalizationGroups, string $identifierName = self::DEFAULT_IDENTIFIER_NAME)
    {
        parent::__construct($resourceClass,
            denormalizationGroups: $denormalizationGroups,
            identifierName: $identifierName);
    }

    /**
     * Simulates a POST request.
     */
    public function addItem(mixed $data, array $filters = [], array $uriVariables = []): mixed
    {
        return $this->dataProcessor->process($data, new Post(), $uriVariables,
            $this->createContext(Request::METHOD_POST, null, $filters));
    }

    /**
     * Simulates a PUT request.
     */
    public function replaceItem(?string $identifier, mixed $data, mixed $previousData,
        array $filters = [], array $uriVariables = []): mixed
    {
        if ($identifier !== null) {
            $uriVariables[$this->identifierName] = $identifier;
        }

        return $this->dataProcessor->process($data, new Put(), $uriVariables,
            $this->createContext(Request::METHOD_PUT, $identifier, $filters, $previousData));
    }

    /**
     * Simulates a PATCH request.
     */
    public function updateItem(?string $identifier, mixed $data, mixed $previousData,
        array $filters = [], array $uriVariables = []): mixed
    {
        if ($identifier !== null) {
            $uriVariables[$this->identifierName] = $identifier;
        }

        return $this->dataProcessor->process($data, new Patch(), $uriVariables,
            $this->createContext(Request::METHOD_PATCH, $identifier, $filters, $previousData));
    }

    /**
     * Simulates a DELETE request.
     */
    public function removeItem(?string $identifier, mixed $data,
        array $filters = [], array $uriVariables = []): void
    {
        if ($identifier !== null) {
            $uriVariables[$this->identifierName] = $identifier;
        }

        $this->dataProcessor->process($data, new Delete(), $uriVariables,
            $this->createContext(Request::METHOD_DELETE, $identifier, $filters, null));
    }
}
