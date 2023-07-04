<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Options;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

class LocalDataAccessChecker
{
    private const LOCAL_DATA_CONFIG_NODE = 'local_data';
    private const LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE = 'local_data_attribute';
    private const READ_POLICY_CONFIG_NODE = 'read_policy';
    private const ALLOW_LOCAL_QUERY_CONFIG_NODE = 'allow_query';

    private const ALLOW_LOCAL_QUERY_KEY = 'allow_query';
    private const ENTITY_OBJECT_ALIAS = 'entity';

    private const READ_POLICY_PREFIX = '@read-local-data:';

    /** @var array */
    private $attributeConfig = [];

    /** @var array */
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
            ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may request read the local data attribute.')
            ->end()
            ->booleanNode(self::ALLOW_LOCAL_QUERY_CONFIG_NODE)
            ->defaultValue(false)
            ->info('Indicates whether the local data attribute can be used in local queries.')
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
            $attributeConfigEntry[self::ALLOW_LOCAL_QUERY_KEY] = $configEntry[self::ALLOW_LOCAL_QUERY_CONFIG_NODE] ?? false;
            $this->attributeConfig[$localDataAttributeName] = $attributeConfigEntry;

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is not readable by default
            $this->policies[self::READ_POLICY_PREFIX.$localDataAttributeName] = $configEntry[self::READ_POLICY_CONFIG_NODE] ?? 'false';
        }
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    /**
     * @throws ApiError
     */
    public function checkRequestedLocalDataAttributes(array $localDataAttributes): void
    {
        foreach ($localDataAttributes as $localDataAttribute) {
            $this->checkRequestedLocalDataAttribute($localDataAttribute);
        }
    }

    /**
     * Iterates over all (local data aware) entities and removes those local data attributes which the current user
     * is not authorized to see for the respective entity (sets them to null).
     */
    public function enforceLocalDataAccessControlPolicies(array $entities, array $options, AbstractAuthorizationService $authorizationService): void
    {
        foreach (Options::getLocalDataAttributes($options) as $localDataAttribute) {
            foreach ($entities as $entity) {
                if ($entity instanceof LocalDataAwareInterface &&
                    !$authorizationService->isGranted(self::READ_POLICY_PREFIX.$localDataAttribute, $entity, self::ENTITY_OBJECT_ALIAS)) {
                    $entity->setLocalDataValue($localDataAttribute, null);
                }
            }
        }
    }

    /**
     * @throws ApiError
     */
    private function checkRequestedLocalDataAttribute(string $attributeName)
    {
        $attributeConfigEntry = $this->attributeConfig[$attributeName] ?? null;
        if ($attributeConfigEntry === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('local data attribute \'%s\' undefined', $attributeName));
        }
    }
}
