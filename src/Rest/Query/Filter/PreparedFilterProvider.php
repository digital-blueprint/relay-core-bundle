<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Rest\Query\Filter;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

class PreparedFilterProvider
{
    private const ROOT_CONFIG_NODE = 'prepared_filters';
    private const NAME_CONFIG_NODE = 'name';
    private const FILTER_CONFIG_NODE = 'filter';
    private const APPLY_POLICY_CONFIG_NODE = 'apply_policy';

    private const FILTER_CONFIG_KEY = 'filter';
    private const FILTER_PARAMETER_PREFIX = 'filter';

    private const POLICY_PREFIX = '@apply-filter:';

    /** @var array */
    private $config = [];

    /** @var array */
    private $policies = [];

    public static function getConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::NAME_CONFIG_NODE)
            ->info('The name of the prepared filter.')
            ->end()
            ->scalarNode(self::APPLY_POLICY_CONFIG_NODE)
            ->defaultValue('false')
            ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may apply the prepared filter.')
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
            $filterName = $configEntry[self::NAME_CONFIG_NODE];

            if (isset($this->config[$filterName])) {
                throw new \RuntimeException(sprintf('multiple config entries for prepared filter \'%s\'', $filterName));
            }
            $attributeConfigEntry = [];
            $attributeConfigEntry[self::FILTER_CONFIG_KEY] = $configEntry[self::FILTER_CONFIG_NODE] ?? '';
            $this->config[$filterName] = $attributeConfigEntry;

            // the filter is not applicable by default
            $this->policies[self::POLICY_PREFIX.$filterName] = $configEntry[self::APPLY_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    /**
     * @throws \Exception
     */
    public function getPreparedFilterById(string $id): Filter
    {
        $preparedFilterConfig = $this->config[$id] ?? null;
        if ($preparedFilterConfig === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, 'prepared filter undefined: '.$id);
        }

        $filterQueryParameters = [];
        parse_str($preparedFilterConfig[self::FILTER_CONFIG_KEY], $filterQueryParameters);

        return FromQueryFilterCreator::createFilterFromQueryParameters($filterQueryParameters[self::FILTER_PARAMETER_PREFIX]);
    }
}
