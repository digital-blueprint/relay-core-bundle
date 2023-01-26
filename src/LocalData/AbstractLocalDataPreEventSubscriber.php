<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/*
 * Abstract implementation of a configurable local data provider pre event subscriber.
 */
abstract class AbstractLocalDataPreEventSubscriber extends AbstractAuthorizationService implements EventSubscriberInterface
{
    protected const ROOT_CONFIG_NODE = 'local_query_mapping';
    protected const SOURCE_ATTRIBUTES_CONFIG_NODE = 'source_attributes';
    protected const LOCAL_DATA_ATTRIBUTE_CONFIG_NODE = 'local_data_attribute';
    protected const AUTHORIZATION_EXPRESSION_CONFIG_NODE = 'authorization_expression';
    protected const DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE = 'default_value';
    protected const DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE = 'default_values';

    private const SOURCE_ATTRIBUTES_KEY = 'source';
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
            $attributeMapEntry[self::SOURCE_ATTRIBUTES_KEY] = $configMappingEntry[self::SOURCE_ATTRIBUTES_CONFIG_NODE];

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

        if (!empty($rightExpressions)) {
            parent::setConfig(parent::createConfig($rightExpressions));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [static::getSubscribedEventName() => 'onPre'];
    }

    public static function getSubscribedEventName(): string
    {
        throw new \RuntimeException(sprintf('child classes must implement the \'%s\' method', __METHOD__));
    }

    public function onPre(LocalDataPreEvent $preEvent)
    {
        $filters = [];

        // matriculateNumber[exact]:0011675
        foreach ($preEvent->getQueryParameters() as $queryParameterName => $queryParameterValue) {
            if (($attributeMapEntry = $this->attributeMapping[$queryParameterName] ?? null) !== null) {
                $sourceAttributeName = $attributeMapEntry[self::SOURCE_ATTRIBUTES_KEY][0];
                $filters[$sourceAttributeName] = $queryParameterValue;
            }
        }

        $preEvent->setQueryParameters(['filters' => $filters]);
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
                    ->arrayNode(self::SOURCE_ATTRIBUTES_CONFIG_NODE)
                        ->info('The list of source attributes to map to the local data attribute ordered by preferred usage. If an attribute is not found, the next attribute in the list is used.')
                        ->scalarPrototype()->end()
                    ->end()
                    ->scalarNode(self::AUTHORIZATION_EXPRESSION_CONFIG_NODE)
                        ->defaultValue('false')
                        ->info('A boolean expression evaluable by the Symfony Expression Language determining whether the current user may request read the local data attribute.')
                    ->end()
                    ->scalarNode(self::DEFAULT_VALUE_ATTRIBUTE_CONFIG_NODE)
                        ->info('The default value for scalar (i.e. non-array) attributes. If none is specified, an exception is thrown in case none of the source attributes is found.')
                    ->end()
                    ->arrayNode(self::DEFAULT_VALUES_ATTRIBUTE_CONFIG_NODE)
                        ->defaultValue(self::ARRAY_VALUE_NOT_SPECIFIED)
                        ->info('The default value for array type attributes. If none is specified, an exception is thrown in case none of the source attributes is found.')
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
