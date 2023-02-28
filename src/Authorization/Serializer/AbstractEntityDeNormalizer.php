<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationConfigDefinition;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class AbstractEntityDeNormalizer extends AbstractAuthorizationService implements ContextAwareNormalizerInterface, NormalizerAwareInterface, ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    private const ENTITY_SHORT_NAME_KEY = 'short_name';
    private const ENTITY_CLASS_NAME_KEY = 'class_name';
    private const ENTITY_ATTRIBUTE_NAMES_KEY = 'attribute_names';

    private const CONTEXT_GROUPS_KEY = 'groups';
    private const ENTITY_OBJECT_ALIAS = 'entity';

    /** @var array */
    private $entityClassNameToReadAttributeNamesMapping = [];

    /** @var array */
    private $entityClassNameToWriteAttributeNamesMapping = [];

    public static function showAttributes(array &$context, string $entityShortName, array $attributeNames)
    {
        foreach ($attributeNames as $attributeName) {
            $context[self::CONTEXT_GROUPS_KEY][] = self::toReadAttributeId($entityShortName, $attributeName);
        }
    }

    public static function hideAttributes(array &$context, string $entityShortName, array $attributeNames)
    {
        foreach ($attributeNames as $attributeName) {
            Tools::removeValueFromArray($context[self::CONTEXT_GROUPS_KEY], self::toReadAttributeId($entityShortName, $attributeName));
        }
    }

    public static function acceptAttributes(array &$context, string $entityShortName, array $attributeNames)
    {
        foreach ($attributeNames as $attributeName) {
            $context[self::CONTEXT_GROUPS_KEY][] = self::toWriteAttributeId($entityShortName, $attributeName);
        }
    }

    public static function rejectAttributes(array &$context, string $entityShortName, array $attributeNames)
    {
        foreach ($attributeNames as $attributeName) {
            Tools::removeValueFromArray($context[self::CONTEXT_GROUPS_KEY], self::toWriteAttributeId($entityShortName, $attributeName));
        }
    }

    /**
     * Method for bundle config injection. Don't call in your code  (use @see AbstractEntityDeNormalizer::configureEntities() instead).
     */
    public function setEntitiesConfig(array $config): void
    {
        $this->loadConfig($config[AuthorizationConfigDefinition::ENTITIES_CONFIG_NODE]);
    }

    public function configureEntities(array $entities): void
    {
        $this->loadConfig($entities);
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entityClassName = get_class($object);
        $mapEntry = $this->entityClassNameToReadAttributeNamesMapping[$entityClassName];
        $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

        foreach ($mapEntry[self::ENTITY_ATTRIBUTE_NAMES_KEY] as $attributeName) {
            $attributeId = self::toReadAttributeId($entityShortName, $attributeName);
            if ($this->isGranted($attributeId, $object, self::ENTITY_OBJECT_ALIAS)) {
                $context[self::CONTEXT_GROUPS_KEY][] = $attributeId;
            }
        }

        $this->onNormalize($object, $entityShortName, $context);

        // Make sure we're not called twice
        $context[self::getUniqueNormalizerAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     *  {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if ($this->entityClassNameToReadAttributeNamesMapping === null || is_object($data) === false) {
            return false;
        }

        $entityClassName = get_class($data);

        // Make sure we're not called twice
        if (isset($context[self::getUniqueNormalizerAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToReadAttributeNamesMapping);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $entityClassName = $type;
        $mapEntry = $this->entityClassNameToWriteAttributeNamesMapping[$entityClassName];
        $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

        foreach ($mapEntry[self::ENTITY_ATTRIBUTE_NAMES_KEY] as $attributeName) {
            $attributeId = self::toWriteAttributeId($entityShortName, $attributeName);
            if ($this->isGranted($attributeId, $data, self::ENTITY_OBJECT_ALIAS)) {
                $context[self::CONTEXT_GROUPS_KEY][] = $attributeId;
            }
        }

        $this->onDeormalize($data, $entityClassName, $entityShortName, $context);

        $context[self::getUniqueDenormalizerAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     *  {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if ($this->entityClassNameToWriteAttributeNamesMapping === null) {
            return false;
        }

        $entityClassName = $type;

        // Make sure we're not called twice
        if (isset($context[self::getUniqueDenormalizerAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToWriteAttributeNamesMapping);
    }

    protected function onNormalize(object $entity, string $entityShortName, array &$context)
    {
    }

    protected function onDeormalize(array $entityData, string $entityClassName, string $entityShortName, array &$context)
    {
    }

    private function loadConfig(array $entityConfigNodes): void
    {
        $roleExpressions = [];
        foreach ($entityConfigNodes as $entityShortName => $entityNode) {
            $entityClassName = $entityNode[AuthorizationConfigDefinition::ENTITY_CLASS_NAME_CONFIG_NODE];
            $attributeNames = [];

            foreach ($entityNode[AuthorizationConfigDefinition::ENTITY_READ_ACCESS_CONFIG_NODE] ?? [] as $attributeName => $attributeAuthorizationExpression) {
                $roleExpressions[self::toReadAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                $attributeNames[] = $attributeName;
            }
            $this->entityClassNameToReadAttributeNamesMapping[$entityClassName] = [
                self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                self::ENTITY_ATTRIBUTE_NAMES_KEY => $attributeNames,
            ];

            $attributeNames = [];
            foreach ($entityNode[AuthorizationConfigDefinition::ENTITY_WRITE_ACCESS_CONFIG_NODE] ?? [] as $attributeName => $attributeAuthorizationExpression) {
                $roleExpressions[self::toWriteAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                $attributeNames[] = $attributeName;
            }
            $this->entityClassNameToWriteAttributeNamesMapping[$entityClassName] = [
                self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                self::ENTITY_ATTRIBUTE_NAMES_KEY => $attributeNames,
            ];
        }

        $this->configure($roleExpressions);
    }

    private static function toReadAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':output:'.$attributeName;
    }

    private static function toWriteAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':input:'.$attributeName;
    }

    private static function getUniqueNormalizerAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.$entityClassName.'normalizer';
    }

    private static function getUniqueDenormalizerAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.$entityClassName.'denoormalizer';
    }
}
