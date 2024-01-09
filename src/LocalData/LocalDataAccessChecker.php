<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

class LocalDataAccessChecker
{
    private const LOCAL_DATA_CONFIG_NODE = 'local_data';
    private const LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE = 'local_data_attribute';
    private const READ_POLICY_CONFIG_NODE = 'read_policy';

    /*
     * @deprecated since version 0.1.119
     */
    private const ALLOW_LOCAL_QUERY_CONFIG_NODE = 'allow_query';

    private const READ_POLICY_PREFIX = '@read-local-data:';

    /** @var array[] */
    private $attributeConfig = [];

    /** @var string[] */
    private $policies = [];

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
            ->booleanNode(self::ALLOW_LOCAL_QUERY_CONFIG_NODE)
            ->defaultValue(false)
            ->info('Indicates whether the local data attribute can be used in local queries.')
            ->setDeprecated('since version 0.1.119')
            ->end()
            ->end()
            ->end()
        ;
    }

    public function loadConfig(array $config): void
    {
        foreach ($config[self::LOCAL_DATA_CONFIG_NODE] ?? [] as $configEntry) {
            $localDataAttributeName = $configEntry[self::LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE];

            if (isset($this->attributeConfig[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple config entries for local data attribute \'%s\'', $localDataAttributeName));
            }
            $attributeConfigEntry = [];
            $this->attributeConfig[$localDataAttributeName] = $attributeConfigEntry;

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is not readable by default
            $this->policies[self::getReadPolicyName($localDataAttributeName)] = $configEntry[self::READ_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    public function getConfiguredLocalDataAttributeNames(): array
    {
        return array_keys($this->attributeConfig);
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    public function isGrantedReadAccess(string $localDataAttributeName, AbstractAuthorizationService $authorizationService): bool
    {
        return $authorizationService->isGranted(self::getReadPolicyName($localDataAttributeName));
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
