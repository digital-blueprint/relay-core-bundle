<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AuthorizationConfigDefinition
{
    /* config array keys */
    public const AUTHORIZATION_CONFIG_NODE = 'authorization';
    public const POLICIES_CONFIG_NODE = 'policies';
    public const ATTRIBUTES_CONFIG_NODE = 'attributes';
    public const ENTITIES_CONFIG_NODE = 'entities';
    public const ENTITY_READ_ACCESS_CONFIG_NODE = 'read_access';
    public const ENTITY_WRITE_ACCESS_CONFIG_NODE = 'write_access';
    public const ENTITY_CLASS_NAME_CONFIG_NODE = 'class_name';

    private const POLICIES_KEY = 'policies';
    private const ATTRIBUTES_KEY = 'attributes';
    private const ENTITIES_KEY = 'entities';
    private const ENTITY_READ_ACCESS_KEY = 'read_access';
    private const ENTITY_WRITE_ACCESS_KEY = 'write_access';
    private const ENTITY_SHORT_NAME_KEY = 'short_name';
    private const ENTITY_CLASS_NAME_KEY = 'class_name';

    /** @var array */
    private $configDefinition;

    public static function create(): AuthorizationConfigDefinition
    {
        return new AuthorizationConfigDefinition();
    }

    public function __construct()
    {
        $this->configDefinition = [];
    }

    public function addPolicy(string $policyName, string $defaultExpression = 'false', string $info = ''): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::POLICIES_KEY, [$policyName, $defaultExpression, $info]);

        return $this;
    }

    public function addAttribute(string $attributeName, string $defaultExpression = 'null', string $info = ''): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::ATTRIBUTES_KEY, [$attributeName, $defaultExpression, $info]);

        return $this;
    }

    public function addEntity(string $entityShortName, string $entityClassName, array $readAttributes = [], array $writeAttributes = []): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::ENTITIES_KEY, [
            self::ENTITY_SHORT_NAME_KEY => $entityShortName,
            self::ENTITY_CLASS_NAME_KEY => $entityClassName,
            self::ENTITY_READ_ACCESS_KEY => $readAttributes,
            self::ENTITY_WRITE_ACCESS_KEY => $writeAttributes,
        ]);

        return $this;
    }

    public function getNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::AUTHORIZATION_CONFIG_NODE);

        $rightsNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::POLICIES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->configDefinition[self::POLICIES_KEY] ?? [] as $policyDefinition) {
            $rightsNodeChildBuilder->scalarNode($policyDefinition[0])
                ->defaultValue($policyDefinition[1] ?? 'false')
                ->info($policyDefinition[2] ?? '')
                ->end();
        }

        $attributesNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ATTRIBUTES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->configDefinition[self::ATTRIBUTES_KEY] ?? [] as $attributeDefinition) {
            $attributesNodeChildBuilder->scalarNode($attributeDefinition[0])
                ->defaultValue($attributeDefinition[1] ?? 'null')
                ->info($attributeDefinition[2] ?? '')
                ->end();
        }

        $entitiesNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ENTITIES_CONFIG_NODE)
            ->children();
        foreach ($this->configDefinition[self::ENTITIES_KEY] ?? [] as $entityDefinition) {
            $entityChildBuilder = $entitiesNodeChildBuilder->arrayNode($entityDefinition[self::ENTITY_SHORT_NAME_KEY])
                ->children();
            $entityChildBuilder->scalarNode(self::ENTITY_CLASS_NAME_CONFIG_NODE)
                ->defaultValue($entityDefinition[self::ENTITY_CLASS_NAME_KEY])
                ->info('The entity class name. There is no need to change the default value.')
                ->end();

            $entityReadAccessChildBuilder = $entityChildBuilder->arrayNode(self::ENTITY_READ_ACCESS_CONFIG_NODE)
                ->children();
            foreach ($entityDefinition[self::ENTITY_READ_ACCESS_KEY] ?? [] as $attributeName) {
                $entityReadAccessChildBuilder->scalarNode($attributeName)
                    ->defaultValue('false')
                    ->info(sprintf('The conditional read policy expression for attribute \'%s\'.', $attributeName))
                    ->end();
            }

            $entityWriteAccessChildBuilder = $entityChildBuilder->arrayNode(self::ENTITY_WRITE_ACCESS_CONFIG_NODE)
                ->children();
            foreach ($entityDefinition[self::ENTITY_WRITE_ACCESS_KEY] ?? [] as $attributeName) {
                $entityWriteAccessChildBuilder->scalarNode($attributeName)
                    ->defaultValue('false')
                    ->info(sprintf('The conditional write policy expression for attribute \'%s\'.', $attributeName))
                    ->end();
            }
        }

        return $treeBuilder->getRootNode()->addDefaultsIfNotSet();
    }
}
