<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @internal
 */
class PreparedFilters
{
    private const ROOT_CONFIG_NODE = 'prepared_filters';
    private const IDENTIFIER_CONFIG_NODE = 'id';
    private const FILTER_CONFIG_NODE = 'filter';
    private const APPLY_POLICY_CONFIG_NODE = 'apply_policy';

    private const FILTER_CONFIG_KEY = 'filter';
    private const POLICY_PREFIX = '@apply-filter:';

    /** @var array[] */
    private array $config = [];

    /** @var string[] */
    private array $policies = [];

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::IDENTIFIER_CONFIG_NODE)
            ->info('The identifier of the prepared filter.')
            ->end()
            ->scalarNode(self::APPLY_POLICY_CONFIG_NODE)
            ->defaultValue('false')
            ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may apply the prepared filter. Available parameters: user.')
            ->end()
            ->scalarNode(self::FILTER_CONFIG_NODE)
            ->defaultValue('')
            ->info('The filter in URL query string format.')
            ->end()
            ->end()
            ->end()
        ;
    }

    public function loadConfig(array $config): void
    {
        foreach ($config[self::ROOT_CONFIG_NODE] ?? [] as $configEntry) {
            $filterId = $configEntry[self::IDENTIFIER_CONFIG_NODE];

            if (isset($this->config[$filterId])) {
                throw new \RuntimeException(sprintf('multiple config entries for prepared filter \'%s\'', $filterId));
            }
            $attributeConfigEntry = [];
            $attributeConfigEntry[self::FILTER_CONFIG_KEY] = $configEntry[self::FILTER_CONFIG_NODE] ?? '';
            $this->config[$filterId] = $attributeConfigEntry;

            // applying the filter is forbidden by default
            $this->policies[self::getPolicyNameByFilterIdentifier($filterId)] = $configEntry[self::APPLY_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    public static function getPolicyNameByFilterIdentifier(string $filterIdentifier): string
    {
        return self::POLICY_PREFIX.$filterIdentifier;
    }

    public function getPreparedFilterQueryString(string $filterIdentifier): ?string
    {
        $preparedFilterConfig = $this->config[$filterIdentifier] ?? null;
        if ($preparedFilterConfig === null) {
            return null;
        }

        return $preparedFilterConfig[self::FILTER_CONFIG_KEY];
    }
}
