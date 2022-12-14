<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/*
 * Abstract implementation of a configurable local data provider post event subscriber.
 * It is intended to be derived by local data aware entity post event subscribers.
 * A mapping between source attribute and local data attribute,
 * and default values for the attributes can be specified by means of the deriving event subscriber's bundle config.
 * If no default value is specified, an exception is thrown in the case the mapped source attribute is not found.
 */
abstract class AbstractLocalDataPostEventSubscriber extends AbstractAuthorizationService implements EventSubscriberInterface
{
    protected const ROOT_CONFIG_NODE = 'local_data_mapping';
    protected const SOURCE_ATTRIBUTE_CONFIG_NODE = 'source_attribute';
    protected const LOCAL_DATA_ATTRIBUTE_CONFIG_NODE = 'local_data_attribute';
    protected const AUTHORIZATION_EXPRESSION_CONFIG_NODE = 'authorization_expression';
    protected const DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE = 'default_value';
    protected const DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE = 'default_values';

    private const SOURCE_ATTRIBUTE_KEY = 'source';
    private const DEFAULT_VALUE_KEY = 'default';

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

            $defaultValue = $configMappingEntry[self::DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE] ??
                ((($defaultArray = $configMappingEntry[self::DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE]) !== self::ARRAY_VALUE_NOT_SPECIFIED) ? $defaultArray : null);
            if ($defaultValue !== null) {
                $attributeMapEntry[self::DEFAULT_VALUE_KEY] = $defaultValue;
            }

            $this->attributeMapping[$localDataAttributeName] = $attributeMapEntry;

            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is visible false by default
            $rightExpressions[$localDataAttributeName] = $configMappingEntry[self::AUTHORIZATION_EXPRESSION_CONFIG_NODE] ?? 'false';
        }

        if (!empty($rightExpressions)) {
            parent::setConfig(parent::createConfig($rightExpressions));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [static::getSubscribedEventName() => 'onPost'];
    }

    public static function getSubscribedEventName(): string
    {
        throw new \RuntimeException(sprintf('child classes must implement the \'%s\' method', __METHOD__));
    }

    public function onPost(LocalDataPostEvent $postEvent)
    {
        $sourceData = $postEvent->getSourceData();

        foreach ($this->attributeMapping as $localDataAttributeName => $attributeMapEntry) {
            if ($postEvent->isLocalDataAttributeRequested($localDataAttributeName)) {
                if (!$this->isGranted($localDataAttributeName)) {
                    throw ApiError::withDetails(Response::HTTP_UNAUTHORIZED, sprintf('access to local data attribute \'%s\' denied', $localDataAttributeName));
                }

                $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTE_KEY];
                $attributeValue = $sourceData[$sourceAttributeName] ?? $attributeMapEntry[self::DEFAULT_VALUE_KEY] ?? null;
                if ($attributeValue !== null) {
                    $postEvent->setLocalDataAttribute($localDataAttributeName, $attributeValue);
                } else {
                    throw ApiError::withDetails(Response::HTTP_INTERNAL_SERVER_ERROR, sprintf('attribute \'%s\' not available in source data', $sourceAttributeName));
                }
            }
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
                    ->scalarNode(self::SOURCE_ATTRIBUTE_CONFIG_NODE)->end()
                    ->scalarNode(self::LOCAL_DATA_ATTRIBUTE_CONFIG_NODE)->end()
                    ->scalarNode(self::AUTHORIZATION_EXPRESSION_CONFIG_NODE)
                        ->defaultValue('false')
                    ->end()
                    ->scalarNode(self::DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE)
                        ->info('The default value for scalar (non-array) attributes. If none is specified, an exception is thrown in the case the source attribute is not found.')
                    ->end()
                    ->arrayNode(self::DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE)
                        ->defaultValue(self::ARRAY_VALUE_NOT_SPECIFIED)
                        ->info('The default value for array type attributes. If none is specified, an exception is thrown in the case the source attribute is not found.')
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
