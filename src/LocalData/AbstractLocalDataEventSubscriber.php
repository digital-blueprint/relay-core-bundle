<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\ExpressionLanguage\ExpressionLanguage;
use Dbp\Relay\CoreBundle\Rest\Options;
use Dbp\Relay\CoreBundle\Rest\Query\Filter\Filter;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
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
    protected const MAP_FILTERS_CONFIG_NODE = 'map_filters';

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const IS_ARRAY_KEY = 'is_array';
    private const MAP_VALUE_KEY = 'map_value';
    private const MAP_FILTERS_KEY = 'map_query';

    private const MAP_VALUE_VALUE_PARAMETER = 'value';
    private const MAP_FILTERS_FILTERS_PARAMETER = 'filters';

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
                         ->info('An attribute expression that takes the source attribute\'s value (\''.self::MAP_VALUE_VALUE_PARAMETER.'\' parameter, can be null) as an input and returns the desired (transformed) value for the local data attribute.')
                    ->end()
                    ->scalarNode(self::MAP_FILTERS_CONFIG_NODE)
                        ->info('An attribute expression that takes the given list of filters for the attribute (\''.self::MAP_FILTERS_FILTERS_PARAMETER.'\' parameter of type Filter array, can be empty) as an input and returns the desired (transformed) list of filters for the attribute.')
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
            $attributeMapEntry[self::MAP_FILTERS_KEY] = $configMappingEntry[self::MAP_FILTERS_CONFIG_NODE] ?? null;

            $this->attributeMapping[$localDataAttributeName] = $attributeMapEntry;
        }
    }

    /**
     * @throws \Exception
     */
    public function onEvent(Event $event)
    {
        $expressionLanguage = null;

        if ($event instanceof LocalDataPreEvent) {
            $combinedFilter = Filter::create();
            foreach ($event->getPendingQueryParameters() as $localQueryAttributeName => $localQueryAttributeValue) {
                if (($attributeMapEntry = $this->attributeMapping[$localQueryAttributeName] ?? null) !== null) {
                    if (($mappingExpression = $attributeMapEntry[self::MAP_FILTERS_KEY]) !== null) {
                        // prepared filter expression:
                        $expressionLanguage = $expressionLanguage ?? $this->getExpressionLanguage();
                        $localFilter = $expressionLanguage->evaluate($mappingExpression);
                        if ($localFilter instanceof Filter === false) {
                            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                        $combinedFilter->combineWith($localFilter);
                    } else {
                        // filter by local data attribute:
                        $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY];
                        $combinedFilter->getRootNode()
                                    ->icontains($sourceAttributeName, $localQueryAttributeValue);
                    }

                    $event->tryPopPendingQueryParameter($localQueryAttributeName);
                }
            }

            $this->onPreEvent($event, $combinedFilter);
        } elseif ($event instanceof LocalDataPostEvent) {
            $localDataAttributes = [];
            foreach ($event->getPendingRequestedAttributes() as $localDataAttributeName) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null) {
                    $attributeValue = $event->getSourceData()[$attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY]] ?? null;

                    if (($mappingExpression = $attributeMapEntry[self::MAP_VALUE_KEY]) !== null) {
                        $expressionLanguage = $expressionLanguage ?? $this->getExpressionLanguage();
                        $attributeValue = $expressionLanguage->evaluate($mappingExpression, [
                            self::MAP_VALUE_VALUE_PARAMETER => $attributeValue,
                        ]);
                    }

                    if ($attributeValue !== null) {
                        $is_array_attribute = $attributeMapEntry[self::IS_ARRAY_KEY];
                        if (is_array($attributeValue)) {
                            $attributeValue = $is_array_attribute ? $attributeValue : ($attributeValue[0] ?? null);
                        } else {
                            $attributeValue = $is_array_attribute ? [$attributeValue] : $attributeValue;
                        }
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
     * Override this if you want to add the filter definition to the request.
     */
    protected function onPreEvent(LocalDataPreEvent $preEvent, Filter $localFilter)
    {
        if ($localFilter->isEmpty() === false) {
            $options = $preEvent->getOptions();
            if ($existingFilter = $options[Options::FILTER] ?? null) {
                $existingFilter->combineWith($localFilter);
            } else {
                $options[Options::FILTER] = $localFilter;
            }

            $preEvent->setOptions($options);
        }
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

    private function getExpressionLanguage(): ExpressionLanguage
    {
        return new ExpressionLanguage(['Filter' => Filter::create()]);
    }
}
