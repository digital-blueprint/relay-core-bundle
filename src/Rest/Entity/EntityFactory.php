<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Entity;

use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AbstractEntityFactory
{
/    /**
//     * @var PropertyMetadataFactoryInterface
//     */
//    private $propertyMetadataFactory;
//
//    /**
//     * @var PropertyNameCollectionFactoryInterface
//     */
//    private $propertyNameCollectionFactory;

    /**
     * @var array
     */
    private $pathMapping;

    /**
     * @var array
     */
    private $paths;

    /**
     * @var string
     */
    private $entityClass;

    public function __construct(string $entityClass, array $paths, array $pathMapping = [])
    {
        $this->entityClass = $entityClass;
        $this->paths = $paths;
        $this->pathMapping = $pathMapping;
    }

//    /**
//     * @required
//     */
//    public function __injectApiPlatformServices(
//        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
//        PropertyMetadataFactoryInterface $propertyMetadataFactory): void
//    {
//        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
//        $this->propertyMetadataFactory = $propertyMetadataFactory;
//    }

    public function createFromDataRow(string $entityClass, array $dataRow): object
    {
        $normalizedEntity = [];

        foreach ($this->paths as $path) {
            $dataPath = $this->pathMapping[$path] ?? $path;
            $dataValue = $dataRow[$dataPath] ?? null;
            $normalizedEntity[$dataPath] = $dataValue;
        }

        return $this->denormalize($normalizedEntity);
    }

    protected function denormalize(array $normalizedEntity): object
    {
        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($normalizedEntity, $this->entityClass);
    }

//    private function foo(string $entityClass, array $entityNormailzationGroups): array
//    {
//        $propertyMetadata = [];
//
//        $propertyNamesFactoryOptions = [];
//        $propertyNamesFactoryOptions['serializer_groups'] = $entityNormailzationGroups;
//
//        foreach ($this->propertyNameCollectionFactory->create($entityClass, $propertyNamesFactoryOptions) as $propertyName) {
//            $propertyMetadata[] = [
//                 'name' => $propertyName,
//                 'type' => $this->propertyMetadataFactory->create($entityClass, $propertyName),
//                 ];
//        }
//
//        return $propertyMetadata;
//    }
}
