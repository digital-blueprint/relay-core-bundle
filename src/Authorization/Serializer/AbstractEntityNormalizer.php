<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Helpers\ApiPlatformHelperFunctions;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

abstract class AbstractEntityNormalizer extends AbstractAuthorizationService implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ROOT_CONFIG_NODE = 'attribute_access';

    private const ENTITY_SHORT_NAME_KEY = 'short_name';
    private const ATTRIBUTE_NAMES_KEY = 'attribute_names';

    private const ENTITY_OBJECT_ALIAS = 'entity';

    /** @var Security */
    private $security;

    /** @var array */
    private $entityClassNames;

    /** @var array */
    private $entityClassNameToAttributeNamesMapping;

    public static function getAttributeAccessConfigNodeDefinition(array $entityShortNameToAttributeNamesMapping): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        foreach ($entityShortNameToAttributeNamesMapping as $entityShortName => $attributeNames) {
            $attributeNodeBuilder = $treeBuilder->getRootNode()->children()->arrayNode($entityShortName)
                ->addDefaultsIfNotSet()
                ->children();
            foreach ($attributeNames as $attributeName) {
                $attributeNodeBuilder->scalarNode($attributeName)
                    ->defaultValue('false')
                    ->info(sprintf('viewer role expression for attribute \'%s\' under entity \'%s\'', $attributeName, $entityShortName))
                    ->end();
            }
        }

        return $treeBuilder->getRootNode();
    }

    private static function toAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':'.$attributeName;
    }

    private static function getUniqueAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.$entityClassName;
    }

    protected function __construct(array $entityClassNames)
    {
        $this->entityClassNames = $entityClassNames;
        $this->entityClassNameToAttributeNamesMapping = [];
    }

    public function setConfig(array $config)
    {
        $configNode = $config[self::ROOT_CONFIG_NODE] ?? [];
        $rightExpressions = [];

        foreach ($this->entityClassNames as $entityClassName) {
            $entityShortName = ApiPlatformHelperFunctions::getShortNameForResource($entityClassName);
            $entityNode = $configNode[$entityShortName] ?? null;
            if ($entityNode !== null) {
                $attributeNames = [];
                foreach ($entityNode as $attributeName => $attributeAuthorizationExpression) {
                    $rightExpressions[self::toAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                    $attributeNames[] = $attributeName;
                }
                $this->entityClassNameToAttributeNamesMapping[$entityClassName] = [
                    self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                    self::ATTRIBUTE_NAMES_KEY => $attributeNames,
                ];
            }
        }

        parent::setConfig(parent::createConfig($rightExpressions));
    }

    /**
     * @required
     */
    public function __inject(Security $security)
    {
        $this->security = $security;
    }

    /**
     *  {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entityClassName = get_class($object);
        $mapEntry = $this->entityClassNameToAttributeNamesMapping[$entityClassName] ?? null;
        if ($mapEntry !== null) {
            $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

            foreach ($mapEntry[self::ATTRIBUTE_NAMES_KEY] as $attributeName) {
                $attributeId = self::toAttributeId($entityShortName, $attributeName);
                if ($this->isGranted($attributeId, $object, self::ENTITY_OBJECT_ALIAS)) {
                    $context['groups'][] = $attributeId;
                }
            }
        }

        $context[self::getUniqueAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     *  {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (!is_object($data)) {
            return false;
        }

        $entityClassName = get_class($data);

        // Make sure we're not called twice
        if (isset($context[self::getUniqueAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToAttributeNamesMapping);
    }
}
