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
     * Iterates over all (local data aware) entities and removes those local data attributes which the current user
     * is not authorized to see for the respective entity (sets them to null).
     * Removes all entities from the result set of local queries which contain attributes that the current user is not allowed to see.
     * (Otherwise the user would be able to refer the value of attributes it is not authorized to see).
     *
     * @param array $entities The list of entities to check
     *
     * @return array the modified list of entities (stripped of all attributes and entities, the current user is not authorized to see)
     */
    public function enforceLocalDataAccessControlPolicies(array $entities, array $options): array
    {
        // map all requested local data attributes (include and query) to 'is requested in query':
        $localDataAttributes = [];
        foreach (LocalData::getLocalDataAttributes($options) as $localDataAttribute) {
            $localDataAttributes[$localDataAttribute] = false;
        }
        foreach (LocalData::getLocalQueryAttributes($options) as $localQueryAttribute => $values) {
            $localDataAttributes[$localQueryAttribute] = true;
        }

        $resultEntities = [];

        if (count($localDataAttributes) === 0) {
            $resultEntities = $entities;
        } else {
            foreach ($localDataAttributes as $localDataAttribute => $isRequestedInQuery) {
                foreach ($entities as $entity) {
                    if ($entity instanceof LocalDataAwareInterface &&
                        !$this->isGranted($localDataAttribute, $entity, self::ENTITY_OBJECT_ALIAS)) {
                        if (!$isRequestedInQuery) {
                            $entity->setLocalDataValue($localDataAttribute, null);
                            $resultEntities[] = $entity;
                        }
                    } else {
                        $resultEntities[] = $entity;
                    }
                }
            }
        }

        return $resultEntities;
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
