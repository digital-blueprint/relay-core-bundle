<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class LocalDataAccessChecker
{
    private const LOCAL_DATA_CONFIG_NODE = 'local_data';
    private const LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE = 'local_data_attribute';
    private const READ_POLICY_CONFIG_NODE = 'read_policy';
    private const ENTITY_SHORT_NAME_CONFIG_NODE = 'entity_short_name';

    // private const IS_FILTERABLE_CONFIG_NODE = 'is_filterable';
    // private const IS_SORTABLE_CONFIG_NODE = 'is_sortable';

    private const READ_POLICY_PREFIX = '@read-local-data:';

    /** @var array[] */
    private array $attributeConfig = [];

    /** @var string[] */
    private array $policies = [];

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::LOCAL_DATA_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE)
            ->info('The name of the local data attribute.')
            ->end()
            ->scalarNode(self::READ_POLICY_CONFIG_NODE)
            ->defaultValue('false')
            ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may read the local data attribute. Available parameters: user.')
            ->end()
            ->scalarNode(self::ENTITY_SHORT_NAME_CONFIG_NODE)
            ->defaultNull()
            ->info('The short name of the entity the local data attribute is defined for. It can be omitted, if the local data attribute is defined for all entities or there is only one entity.')
            ->end()
            //            ->booleanNode(self::IS_FILTERABLE_CONFIG_NODE) // TODO: implement
            //            ->defaultValue(false)
            //            ->info('Indicates whether the local data attribute can be filtered by, given that '.self::READ_POLICY_CONFIG_NODE.' evaluates to true for the current user.')
            //            ->end()
            //            ->booleanNode(self::IS_SORTABLE_CONFIG_NODE) // TODO: implement
            //            ->defaultValue(false)
            //            ->info('Indicates whether the local data attribute can be sorted by, given that '.self::READ_POLICY_CONFIG_NODE.' evaluates to true for the current user.')
            //            ->end()
            ->end()
            ->end()
        ;
    }

    public function loadConfig(array $config): void
    {
        $this->attributeConfig = [];

        foreach ($config[self::LOCAL_DATA_CONFIG_NODE] ?? [] as $configEntry) {
            $localDataAttributeName = $configEntry[self::LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE];

            if (isset($this->attributeConfig[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple config entries for local data attribute \'%s\'', $localDataAttributeName));
            }
            $this->attributeConfig[$localDataAttributeName] = $configEntry;

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is not readable by default
            $this->policies[self::getReadPolicyName($localDataAttributeName)] = $configEntry[self::READ_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    /**
     * @return string[]
     */
    public function getConfiguredLocalDataAttributeNames(?string $entityShortName): array
    {
        return array_keys(array_filter($this->attributeConfig,
            function (array $configEntry) use ($entityShortName) {
                return null === $entityShortName
                    || $entityShortName === ($configEntry[self::ENTITY_SHORT_NAME_CONFIG_NODE] ?? $entityShortName);
            }));
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    public function isGrantedReadAccess(string $localDataAttributeName, AbstractAuthorizationService $authorizationService): bool
    {
        return $authorizationService->isGrantedRole(self::getReadPolicyName($localDataAttributeName));
    }

    /**
     * @throws ApiError
     */
    public function assertLocalDataAttributesAreDefined(array $localDataAttributes): void
    {
        foreach ($localDataAttributes as $localDataAttributeName) {
            $attributeConfigEntry = $this->attributeConfig[$localDataAttributeName] ?? null;
            if ($attributeConfigEntry === null) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('local data attribute \'%s\' undefined', $localDataAttributeName));
            }
        }
    }

    private static function getReadPolicyName(string $localDataAttributeName): string
    {
        return self::READ_POLICY_PREFIX.$localDataAttributeName;
    }
}
