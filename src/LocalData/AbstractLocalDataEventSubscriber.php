<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
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
abstract class AbstractLocalDataEventSubscriber extends AbstractAuthorizationService implements EventSubscriberInterface
{
    protected const ROOT_CONFIG_NODE = 'local_data_mapping';
    protected const SOURCE_ATTRIBUTE_CONFIG_NODE = 'source_attribute';
    protected const LOCAL_DATA_ATTRIBUTE_CONFIG_NODE = 'local_data_attribute';
    protected const AUTHORIZATION_EXPRESSION_CONFIG_NODE = 'authorization_expression';
    protected const ALLOW_LOCAL_QUERY_CONFIG_NODE = 'allow_query';
    protected const DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE = 'default_value';
    protected const DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE = 'default_values';

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const DEFAULT_VALUE_KEY = 'default';
    private const QUERYABLE_KEY = 'queryable';

    /*
     * WORKAROUND: could not find a way to determine whether a Symfony config array node was NOT specified since it provides an empty
     * array in case it is not specified. So I use an array value as default which does not seem to be reproducible by the configurator.
     */
    private const ARRAY_VALUE_NOT_SPECIFIED = [null => null];

    /** @var array */
    private $attributeMapping;

    public function __construct()
    {
        $this->attributeMapping = [];
    }

    public function setConfig(array $config)
    {
        $configNode = $config[self::ROOT_CONFIG_NODE] ?? [];
        $rightExpressions = [];

        foreach ($configNode as $configMappingEntry) {
            $localDataAttributeName = $configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_CONFIG_NODE];

            if (isset($this->attributeMapping[$localDataAttributeName])) {
                throw new \RuntimeException(sprintf('multiple mapping entries for local data attribute %s', $localDataAttributeName));
            }

            $attributeMapEntry = [];
            $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY] = $configMappingEntry[self::SOURCE_ATTRIBUTE_CONFIG_NODE];
            $attributeMapEntry[self::QUERYABLE_KEY] = $configMappingEntry[self::ALLOW_LOCAL_QUERY_CONFIG_NODE];

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

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is visible false by default
            $rightExpressions[$localDataAttributeName] = $configMappingEntry[self::AUTHORIZATION_EXPRESSION_CONFIG_NODE] ?? 'false';
        }

        parent::configure($rightExpressions);
    }

    public static function getSubscribedEvents(): array
    {
        $eventMapping = [];
        foreach (static::getSubscribedEventNames() as $eventName) {
            $eventMapping[$eventName] = 'onEvent';
        }

        return $eventMapping;
    }

    public function onEvent(Event $event)
    {
        if ($event instanceof LocalDataPreEvent) {
            $localQueryParameters = [];
            foreach ($event->getPendingQueryParameters() as $localDataAttributeName => $localDataAttributeValue) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null &&
                    $attributeMapEntry[self::QUERYABLE_KEY] === true) {
                    if (!$this->isGranted($localDataAttributeName)) {
                        throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, sprintf('access to local data attribute \'%s\' denied', $localDataAttributeName));
                    }
                    $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY];
                    $localQueryParameters[$sourceAttributeName] = $localDataAttributeValue;
                    $event->tryPopPendingQueryParameter($localDataAttributeName);
                }
            }

            $this->onPreEvent($event, $localQueryParameters);
        } elseif ($event instanceof LocalDataPostEvent) {
            foreach ($event->getPendingRequestedAttributes() as $localDataAttributeName) {
                if (($attributeMapEntry = $this->attributeMapping[$localDataAttributeName] ?? null) !== null) {
                    if (!$this->isGranted($localDataAttributeName)) {
                        throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, sprintf('access to local data attribute \'%s\' denied', $localDataAttributeName));
                    }

                    $attributeValue = $event->getSourceData()[$attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY]] ?? null;
                    $attributeValue = $attributeValue ?? $attributeMapEntry[self::DEFAULT_VALUE_KEY] ?? null;

                    if ($attributeValue !== null) {
                        $event->setLocalDataAttribute($localDataAttributeName, $attributeValue);
                    } else {
                        throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, sprintf('none of the source attributes available for local data attribute \'%s\'', $localDataAttributeName));
                    }
                }
            }
            $this->onPostEvent($event);
        }
    }

    /**
     * @deprecated Use getLocalDataMappingConfigNodeDefinition instead
     */
    public static function getConfigNode(): NodeDefinition
    {
        return self::getLocalDataMappingConfigNodeDefinition();
    }

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
                    ->scalarNode(self::AUTHORIZATION_EXPRESSION_CONFIG_NODE)
                        ->defaultValue('false')
                        ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may request read the local data attribute.')
                    ->end()
                    ->booleanNode(self::ALLOW_LOCAL_QUERY_CONFIG_NODE)
                        ->defaultValue('false')
                        ->info('Indicates whether the local data attribute can be used in local queries.')
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

    protected static function getSubscribedEventNames(): array
    {
        throw new \RuntimeException(sprintf('child classes must implement the \'%s\' method', __METHOD__));
    }

    protected function onPreEvent(LocalDataPreEvent $preEvent, array $mappedQueryParameters)
    {
    }

    protected function onPostEvent(LocalDataPostEvent $postEvent)
    {
    }
}
