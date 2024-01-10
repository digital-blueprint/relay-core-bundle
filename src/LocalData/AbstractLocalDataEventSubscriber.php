<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

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

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const IS_ARRAY_KEY = 'is_array';

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
        throw new \RuntimeException(sprintf('child classes must override the static \'%s\' method', __METHOD__));
    }

    public function setConfig(array $config)
    {
        $configNode = $config[self::ROOT_CONFIG_NODE] ?? [];

        foreach ($configNode as $configMappingEntry) {
            $localDataAttributeName = $configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_CONFIG_NODE];

            if (isset($this->attributeMapping[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple mapping entries for local data attribute %s', $localDataAttributeName));
            }

            $this->attributeMapping[$localDataAttributeName] = [
                self::SOURCE_ATTRIBUTE_KEY => $configMappingEntry[self::SOURCE_ATTRIBUTE_CONFIG_NODE],
                self::IS_ARRAY_KEY => $configMappingEntry[self::IS_ARRAY_CONFIG_NODE] ?? false,
            ];
        }
    }

    /**
     * @throws \Exception
     */
    public function onEvent(Event $event)
    {
        if ($event instanceof LocalDataPostEvent) {
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
     * Override this if you want to modify the local data attribute values before they are set in the entity.
     *
     * @param array $localDataAttributes A reference to the associative array of local data attributes
     *                                   (keys: local data attribute names, values: local data attribute values)
     */
    protected function onPostEvent(LocalDataPostEvent $postEvent, array &$localDataAttributes)
    {
    }
}
