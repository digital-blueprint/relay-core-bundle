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
    public const ROLES_CONFIG_NODE = 'roles';
    public const RESOURCE_PERMISSIONS_CONFIG_NODE = 'resource_permissions';
    public const ATTRIBUTES_CONFIG_NODE = 'attributes';
    public const ENTITIES_CONFIG_NODE = 'entities';
    public const ENTITY_READ_ACCESS_CONFIG_NODE = 'read_access';
    public const ENTITY_WRITE_ACCESS_CONFIG_NODE = 'write_access';
    public const ENTITY_CLASS_NAME_CONFIG_NODE = 'class_name';

    private const POLICIES_KEY = 'policies';
    private const ROLES_KEY = 'roles';
    private const RESOURCE_PERMISSIONS_KEY = 'resource_permissions';
    private const ATTRIBUTES_KEY = 'attributes';

    private array $configDefinition = [];

    public static function create(): AuthorizationConfigDefinition
    {
        return new AuthorizationConfigDefinition();
    }

    public function addRole(string $roleName, string $defaultExpression = 'false', string $info = ''): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::ROLES_KEY, [$roleName, $defaultExpression, $info]);

        return $this;
    }

    public function addResourcePermission(string $resourcePermissionName, string $defaultExpression = 'false', string $info = ''): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::RESOURCE_PERMISSIONS_KEY, [$resourcePermissionName, $defaultExpression, $info]);

        return $this;
    }

    public function addAttribute(string $attributeName, string $defaultExpression = 'null', string $info = ''): AuthorizationConfigDefinition
    {
        Tools::pushToSubarray($this->configDefinition, self::ATTRIBUTES_KEY, [$attributeName, $defaultExpression, $info]);

        return $this;
    }

    public function getNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::AUTHORIZATION_CONFIG_NODE);

        $rightsNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ROLES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->configDefinition[self::ROLES_KEY] ?? [] as $roleDefinition) {
            $rightsNodeChildBuilder->scalarNode($roleDefinition[0])
                ->defaultValue($roleDefinition[1] ?? 'false')
                ->info($roleDefinition[2] ?? '')
                ->end();
        }

        $rightsNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::RESOURCE_PERMISSIONS_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($this->configDefinition[self::RESOURCE_PERMISSIONS_KEY] ?? [] as $resourcePersmissionDefinition) {
            $rightsNodeChildBuilder->scalarNode($resourcePersmissionDefinition[0])
                ->defaultValue($resourcePersmissionDefinition[1] ?? 'false')
                ->info($resourcePersmissionDefinition[2] ?? '')
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

        return $treeBuilder->getRootNode()->addDefaultsIfNotSet();
    }
}
