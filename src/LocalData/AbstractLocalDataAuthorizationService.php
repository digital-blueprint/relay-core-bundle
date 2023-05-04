<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;

class AbstractLocalDataAuthorizationService extends AbstractAuthorizationService
{
    protected const LOCAL_DATA_CONFIG_NODE = 'local_data';
    protected const LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE = 'local_data_attribute';
    protected const AUTHORIZATION_EXPRESSION_CONFIG_NODE = 'authorization_expression';
    protected const ALLOW_LOCAL_QUERY_CONFIG_NODE = 'allow_query';

    private const ALLOW_LOCAL_QUERY_KEY = 'allow_query';
    private const ENTITY_OBJECT_ALIAS = 'entity';

    /** @var array */
    private $attributeConfig = [];

    public function setConfig(array $config)
    {
        parent::setConfig($config);

        $rightExpressions = [];

        foreach ($config[self::LOCAL_DATA_CONFIG_NODE] ?? [] as $configEntry) {
            $localDataAttributeName = $configEntry[self::LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE];

            if (isset($this->attributeConfig[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple config entries for local data attribute \'%s\'', $localDataAttributeName));
            }
            $attributeConfigEntry = [];
            $attributeConfigEntry[self::ALLOW_LOCAL_QUERY_KEY] = $configEntry[self::ALLOW_LOCAL_QUERY_CONFIG_NODE] ?? false;
            $this->attributeConfig[$localDataAttributeName] = $attributeConfigEntry;

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is visible false by default
            $rightExpressions[$localDataAttributeName] = $configEntry[self::AUTHORIZATION_EXPRESSION_CONFIG_NODE] ?? 'false';
        }

        parent::configure($rightExpressions);
    }

    /**
     * @throws ApiError
     */
    public function checkRequestedLocalDataAttributes(array $options): void
    {
        foreach (LocalData::getLocalDataAttributes($options) as $localDataAttribute) {
            $this->checkRequestedLocalDataAttribute($localDataAttribute, false);
        }

        foreach (array_keys(LocalData::getLocalQueryAttributes($options)) as $localQueryAttribute) {
            $this->checkRequestedLocalDataAttribute($localQueryAttribute, true);
        }
    }

    /**
     * @throws ApiError
     */
    public function denyLocalDataAccessUnlessGranted(array $entities, array $options): void
    {
        $localDataAwareEntities = array_filter($entities, function ($entity) {
            return $entity instanceof LocalDataAwareInterface;
        });

        foreach (LocalData::getLocalDataAttributes($options) as $localDataAttribute) {
            $this->denyAttributeAccessUnlessGranted($localDataAwareEntities, $localDataAttribute);
        }
    }

    /**
     * @throws ApiError
     */
    private function checkRequestedLocalDataAttribute(string $attributeName, bool $checkQueryAllowed)
    {
        $attributeConfigEntry = $this->attributeConfig[$attributeName] ?? null;
        if ($attributeConfigEntry === null) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('local data attribute \'%s\' undefined', $attributeName));
        }

        if ($checkQueryAllowed && !$attributeConfigEntry[self::ALLOW_LOCAL_QUERY_KEY]) {
            throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf('local data attribute \'%s\' not queryable', $attributeName));
        }
    }

    /**
     * @param LocalDataAwareInterface[] $localDataAwareEntities
     *
     * @throws ApiError
     */
    private function denyAttributeAccessUnlessGranted(array $localDataAwareEntities, string $attributeName)
    {
        foreach ($localDataAwareEntities as $localDataAwareEntity) {
            if (!$this->isGranted($attributeName, $localDataAwareEntity, self::ENTITY_OBJECT_ALIAS)) {
                throw ApiError::withDetails(Response::HTTP_FORBIDDEN, sprintf('access to local data attribute \'%s\' denied', $attributeName));
            }
        }
    }

    public static function getLocalDataConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::LOCAL_DATA_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->children()
            ->scalarNode(self::LOCAL_DATA_ATTRIBUTE_NAME_CONFIG_NODE)
            ->info('The name of the local data attribute.')
            ->end()
            ->scalarNode(self::AUTHORIZATION_EXPRESSION_CONFIG_NODE)
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
}
