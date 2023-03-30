<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Exception\ApiError;
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
    protected const DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE = 'default_value';
    protected const DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE = 'default_values';

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const DEFAULT_VALUE_KEY = 'default';
    private const IS_ARRAY_KEY = 'is_array';

    /*
     * WORKAROUND: could not find a way to determine whether a Symfony config array node was NOT specified since it provides an empty
     * array in case it is not specified. So I use an array value as default which does not seem to be reproducible by the configurator.
     */
    private const ARRAY_VALUE_NOT_SPECIFIED = [null => null];

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
            ->scalarNode(self::DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE)
            ->info('The default value for scalar (i.e. non-array) attributes. If none is specified, an exception is thrown in case the source attribute is not found.')
            ->end()
            ->arrayNode(self::DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE)
            ->defaultValue(self::ARRAY_VALUE_NOT_SPECIFIED)
            ->info('The default value for array type attributes. If none is specified, an exception is thrown in case the source attribute is not found.')
            ->scalarPrototype()->end()
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

            $defaultValue = $configMappingEntry[self::DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE] ?? null;
            if ($defaultValue === null) {
                $defaultArray = $configMappingEntry[self::DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE] ?? null;
                if ($defaultArray !== null && $defaultArray !== self::ARRAY_VALUE_NOT_SPECIFIED) {
                    $defaultValue = $defaultArray;
                }
            }

            if ($defaultValue !== null) {
                $attributeMapEntry[self::DEFAULT_VALUE_KEY] = $defaultValue;
            }

            $this->attributeMapping[$localDataAttributeName] = $attributeMapEntry;
        }
    }

    public function onEvent(Event $event)
    {
        if ($event instanceof LocalDataPreEvent) {
            $localQueryParameters = [];
            foreach ($event->getPendingQueryParameters() as $localDataAttributeName => $localDataAttributeValue) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null) {
                    $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY];
                    $localQueryParameters[$sourceAttributeName] = $localDataAttributeValue;
                    $event->tryPopPendingQueryParameter($localDataAttributeName);
                }
            }
            $this->onPreEvent($event, $localQueryParameters);
        } elseif ($event instanceof LocalDataPostEvent) {
            $localDataAttributes = [];
            foreach ($event->getPendingRequestedAttributes() as $localDataAttributeName) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null) {
                    $attributeValue = $event->getSourceData()[$attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY]] ?? null;

                    $is_array_attribute = $attributeMapEntry[self::IS_ARRAY_KEY];
                    if (is_array($attributeValue)) {
                        $attributeValue = $is_array_attribute ? $attributeValue : ($attributeValue[0] ?? null);
                    } else {
                        $attributeValue = $is_array_attribute ? [$attributeValue] : $attributeValue;
                    }
                    $attributeValue = $attributeValue ?? $attributeMapEntry[self::DEFAULT_VALUE_KEY] ?? null;

                    if ($attributeValue !== null) {
                        $localDataAttributes[$localDataAttributeName] = $attributeValue;
                    } else {
                        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, sprintf('none of the source attributes available for local data attribute \'%s\'', $localDataAttributeName));
                    }
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
     * @param array $mappedQueryParameters The associative array of local query attributes (keys: local query attribute names, values: local query attribute values)
     */
    protected function onPreEvent(LocalDataPreEvent $preEvent, array $mappedQueryParameters)
    {
    }

    /**
     * Override this if you want to modify the local data attribute values before they are set.
     *
     * @param array $localDataAttributes A reference to the associative array of local data attributes (keys: local data attribute names, values: local data attribute values)
     */
    protected function onPostEvent(LocalDataPostEvent $postEvent, array &$localDataAttributes)
    {
    }
}
