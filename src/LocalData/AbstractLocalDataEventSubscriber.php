<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

/*
 * Abstract implementation of a configurable local data provider post event subscriber.
 * It is intended to be derived by local data aware entity post event subscribers.
 * A mapping between source attribute and local data attribute,
 * and default values for the attributes can be specified by means of the deriving event subscriber's bundle config.
 * If no default value is specified, an exception is thrown in the case the mapped source attribute is not found.
 */
abstract class AbstractLocalDataEventSubscriber implements EventSubscriberInterface
{
    protected const ROOT_CONFIG_NODE = 'local_data_mapping';
    protected const SOURCE_ATTRIBUTE_CONFIG_NODE = 'source_attribute';
    protected const LOCAL_DATA_ATTRIBUTE_CONFIG_NODE = 'local_data_attribute';
    protected const IS_ARRAY_CONFIG_NODE = 'is_array';
    protected const MAP_VALUE_CONFIG_NODE = 'map_value';
    protected const MAP_QUERY_VALUE_CONFIG_NODE = 'map_query';

    protected const LOCAL_QUERY_PARAMETER_LOCAL_DATA_ATTRIBUTE_KEY = 'local_data_attribute';
    protected const LOCAL_QUERY_PARAMETER_SOURCE_ATTRIBUTE_KEY = 'source_attribute';
    protected const LOCAL_QUERY_PARAMETER_VALUE_KEY = 'value';
    protected const LOCAL_QUERY_PARAMETER_OPERATOR_KEY = 'operator';

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const IS_ARRAY_KEY = 'is_array';
    private const MAP_VALUE_KEY = 'map_value';
    private const MAP_QUERY_VALUE_KEY = 'map_query';

    private const MAP_VALUE_VALUE_PARAMETER = 'value';
    private const MAP_QUERY_VALUES_PARAMETER = 'values';

    /** @var array */
    private $attributeMapping = [];

    public static function getLocalDataMappingConfigNodeDefinition(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::ROOT_CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode(self::LOCAL_DATA_ATTRIBUTE_CONFIG_NODE)
                       ->info('The name of the local data attribute.')
                    ->end()
                    ->scalarNode(self::SOURCE_ATTRIBUTE_CONFIG_NODE)
                        ->info('The source attribute to map to the local data attribute. If the source attribute is not found, the default value is used.')
                    ->end()
                    ->booleanNode(self::IS_ARRAY_CONFIG_NODE)
                        ->info('Specifies whether the local data attribute is expected to be of array type. The value of the local data attribute is converted accordingly, if required.')
                        ->defaultValue(false)
                    ->end()
                    ->scalarNode(self::MAP_VALUE_CONFIG_NODE)
                         ->info('An attribute expression that takes the source attribute\'s value (\'value\' parameter, can be null) as an input and returns the transformed value of the local data attribute.')
                    ->end()
                    ->scalarNode(self::MAP_QUERY_VALUE_CONFIG_NODE)
                        ->info('An attribute expression that takes the given list of values for the query parameter (\'values\' parameter of type string array, can contain null values) as an input and returns the desired transformed list of values for the query parameter.')
                    ->end()
                ->end()
            ->end()
            ;
    }

    public static function getSubscribedEvents(): array
    {
        $eventMapping = [];
        foreach (static::getSubscribedEventNames() as $eventName) {
            $eventMapping[$eventName] = 'onEvent';
        }

        return $eventMapping;
    }

    protected static function getSubscribedEventNames(): array
    {
        throw new \RuntimeException(sprintf('child classes must implement the \'%s\' method', __METHOD__));
    }

    public function setConfig(array $config)
    {
        $configNode = $config[self::ROOT_CONFIG_NODE] ?? [];

        foreach ($configNode as $configMappingEntry) {
            $localDataAttributeName = $configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_CONFIG_NODE];

            if (isset($this->attributeMapping[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple mapping entries for local data attribute %s', $localDataAttributeName));
            }

            $attributeMapEntry = [];
            $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY] = $configMappingEntry[self::SOURCE_ATTRIBUTE_CONFIG_NODE];
            $attributeMapEntry[self::IS_ARRAY_KEY] = $configMappingEntry[self::IS_ARRAY_CONFIG_NODE] ?? false;
            $attributeMapEntry[self::MAP_VALUE_KEY] = $configMappingEntry[self::MAP_VALUE_CONFIG_NODE] ?? null;
            $attributeMapEntry[self::MAP_QUERY_VALUE_KEY] = $configMappingEntry[self::MAP_QUERY_VALUE_CONFIG_NODE] ?? null;

            $this->attributeMapping[$localDataAttributeName] = $attributeMapEntry;
        }
    }

    public function onEvent(Event $event)
    {
        $expressionLanguage = null;

        if ($event instanceof LocalDataPreEvent) {
            $localQueryParameters = [];
            foreach ($event->getPendingQueryParameters() as $localQueryAttributeName => $localQueryAttributeValues) {
                if (($attributeMapEntry = $this->attributeMapping[$localQueryAttributeName] ?? null) !== null) {
                    if (($mappingExpression = $attributeMapEntry[self::MAP_QUERY_VALUE_KEY]) !== null) {
                        $expressionLanguage = $expressionLanguage ?? new ExpressionLanguage();
                        $localQueryAttributeValues = $expressionLanguage->evaluate($mappingExpression, [
                            self::MAP_QUERY_VALUES_PARAMETER => $localQueryAttributeValues,
                        ]);
                    }
                    $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY];
                    foreach ($localQueryAttributeValues as $localDataAttributeValue) {
                        $localQueryParameters[] = [
                            self::LOCAL_QUERY_PARAMETER_LOCAL_DATA_ATTRIBUTE_KEY => $localQueryAttributeName,
                            self::LOCAL_QUERY_PARAMETER_SOURCE_ATTRIBUTE_KEY => $sourceAttributeName,
                            self::LOCAL_QUERY_PARAMETER_VALUE_KEY => $localDataAttributeValue,
                            self::LOCAL_QUERY_PARAMETER_OPERATOR_KEY => LocalData::LOCAL_QUERY_OPERATOR_CONTAINS_CI,
                        ];
                    }
                    $event->tryPopPendingQueryParameter($localQueryAttributeName);
                }
            }

            $this->onPreEvent($event, $localQueryParameters);
        } elseif ($event instanceof LocalDataPostEvent) {
            $localDataAttributes = [];
            foreach ($event->getPendingRequestedAttributes() as $localDataAttributeName) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null) {
                    $attributeValue = $event->getSourceData()[$attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY]] ?? null;

                    if ($attributeValue !== null) {
                        $is_array_attribute = $attributeMapEntry[self::IS_ARRAY_KEY];
                        if (is_array($attributeValue)) {
                            $attributeValue = $is_array_attribute ? $attributeValue : ($attributeValue[0] ?? null);
                        } else {
                            $attributeValue = $is_array_attribute ? [$attributeValue] : $attributeValue;
                        }
                    }

                    if (($mappingExpression = $attributeMapEntry[self::MAP_VALUE_KEY]) !== null) {
                        $expressionLanguage = $expressionLanguage ?? new ExpressionLanguage();
                        $attributeValue = $expressionLanguage->evaluate($mappingExpression, [
                            self::MAP_VALUE_VALUE_PARAMETER => $attributeValue,
                        ]);
                    }
                    $localDataAttributes[$localDataAttributeName] = $attributeValue;
                }
            }

            $this->onPostEvent($event, $localDataAttributes);

            foreach ($localDataAttributes as $localDataAttributeName => $localDataAttributeValue) {
                $event->setLocalDataAttribute($localDataAttributeName, $localDataAttributeValue);
            }
        }
    }

    /**
     * Override this if you want to use the mapped local query attributes in your code.
     *
     * @param array $localQueryAttributes The array of local query attributes in the form:
     *                                    [
     *                                    self::LOCAL_QUERY_PARAMETER_LOCAL_DATA_ATTRIBUTE_KEY => <local data attribute name>,
     *                                    self::LOCAL_QUERY_PARAMETER_SOURCE_ATTRIBUTE_KEY => <source attribute name>,
     *                                    self::LOCAL_QUERY_PARAMETER_VALUE_KEY => <query attribute value>,
     *                                    self::LOCAL_QUERY_PARAMETER_OPERATOR_KEY => <operator>,
     *                                    ]
     */
    protected function onPreEvent(LocalDataPreEvent $preEvent, array $localQueryAttributes)
    {
    }

    /**
     * Override this if you want to modify the local data attribute values before they are set in the entity.
     *
     * @param array $localDataAttributes A reference to the associative array of local data attributes
     *                                   (keys: local data attribute names, values: local data attribute values)
     */
    protected function onPostEvent(LocalDataPostEvent $postEvent, array &$localDataAttributes)
    {
    }
}
