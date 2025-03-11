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
    private const USE_POLICY_CONFIG_NODE = 'use_policy';
    private const FORCE_USE_POLICY_CONFIG_NODE = 'force_use_policy';

    private const FILTER_CONFIG_KEY = 'filter';
    private const USE_POLICY_PREFIX = '@use-filter:';
    private const FORCE_USE_POLICY_PREFIX = '@force-use-filter:';

    /** @var array<int, array<string, mixed>> */
    private array $config = [];

    /** @var array<string, string> */
    private array $usePolicies = [];

    /** @var array<string, string> */
    private array $forceUsePolicies = [];

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::IDENTIFIER_CONFIG_NODE)
            ->info('The identifier of the prepared filter.')
            ->end()
            ->scalarNode(self::USE_POLICY_CONFIG_NODE)
            ->defaultValue('false')
            ->info('A boolean policy expression that determines whether the current user may apply the prepared filter. Available parameters: user.')
            ->end()
            ->scalarNode(self::FORCE_USE_POLICY_CONFIG_NODE)
            ->defaultValue('false')
            ->info('A boolean policy expression that determines whether the usage of the filter is forced for the current user. Available parameters: user.')
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
        $this->config = [];
        $this->usePolicies = [];
        $this->forceUsePolicies = [];

        foreach ($config[self::ROOT_CONFIG_NODE] ?? [] as $configEntry) {
            $filterIdentifier = $configEntry[self::IDENTIFIER_CONFIG_NODE];

            if (isset($this->config[$filterIdentifier])) {
                throw new \RuntimeException(sprintf('multiple config entries for prepared filter \'%s\'', $filterIdentifier));
            }
            $attributeConfigEntry = [];
            $attributeConfigEntry[self::FILTER_CONFIG_KEY] = $configEntry[self::FILTER_CONFIG_NODE] ?? '';
            $this->config[$filterIdentifier] = $attributeConfigEntry;

            // using the filter is forbidden by default
            $this->usePolicies[$filterIdentifier] = $configEntry[self::USE_POLICY_CONFIG_NODE] ?? 'false';
            // forcing the usage of the filter is disabled by default
            $this->forceUsePolicies[$filterIdentifier] = $configEntry[self::FORCE_USE_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    public function getUsePolicies(): array
    {
        return $this->usePolicies;
    }

    public function getForceUsePolicies(): array
    {
        return $this->forceUsePolicies;
    }

    public function isPreparedFilterDefined(string $filterIdentifier): bool
    {
        return isset($this->config[$filterIdentifier]);
    }

    public function getPreparedFilterQueryString(string $filterIdentifier): ?string
    {
        return $this->config[$filterIdentifier][self::FILTER_CONFIG_KEY] ?? null;
    }
}
