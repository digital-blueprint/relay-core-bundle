<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataProviderProvider;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractLocalDataPostEventSubscriber extends AbstractAuthorizationService implements EventSubscriberInterface
{
    public const CONFIG_NODE = 'local_data_mapping';
    public const SOURCE_ATTRIBUTE_KEY = 'source_attribute';
    public const LOCAL_DATA_ATTRIBUTE_KEY = 'local_data_attribute';
    public const AUTHORIZATION_EXPRESSION_KEY = 'authorization_expression';

    /** @var string[] */
    private $attributeMapping;

    public function __construct(UserSessionInterface $userSession, AuthorizationDataProviderProvider $authorizationDataProviderProvider)
    {
        parent::__construct($userSession, $authorizationDataProviderProvider);

        $this->attributeMapping = [];
    }

    public function setConfig(array $config)
    {
        $configNode = $config[self::CONFIG_NODE] ?? [];

        $rights = [];
        foreach ($configNode as $configMappingEntry) {
            if (isset($this->attributeMapping[$configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_KEY]])) {
                throw new \RuntimeException(sprintf('multiple mapping entries for local data attribute %s', $configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_KEY]));
            }
            $this->attributeMapping[$configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_KEY]] = $configMappingEntry[self::SOURCE_ATTRIBUTE_KEY];
            // the name of the local data attribute is used as name for the right to view that attribute
            // the attribute is visible false by default
            $rights[$configMappingEntry[self::LOCAL_DATA_ATTRIBUTE_KEY]] = $configMappingEntry[self::AUTHORIZATION_EXPRESSION_KEY] ?? 'false';
        }

        if (!empty($rights)) {
            parent::setConfig([AbstractAuthorizationService::RIGHTS_CONFIG_ATTRIBUTE => $rights]);
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

        foreach ($this->attributeMapping as $localDataAttributeName => $sourceAttributeName) {
            if ($this->isGranted($localDataAttributeName)) {
                if (($sourceAttributeValue = $sourceData[$sourceAttributeName] ?? null) !== null) {
                    $postEvent->trySetLocalDataAttribute($localDataAttributeName, $sourceAttributeValue);
                } else {
                    throw new \RuntimeException(sprintf('attribute \'%s\' not available in source data', $sourceAttributeName));
                }
            }
        }
    }

    public static function getConfigNode()
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_NODE);

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode(self::SOURCE_ATTRIBUTE_KEY)->end()
                    ->scalarNode(self::LOCAL_DATA_ATTRIBUTE_KEY)->end()
                    ->scalarNode(self::AUTHORIZATION_EXPRESSION_KEY)
                        ->defaultValue('false')
                    ->end()
                ->end()
            ->end()
        ;
    }
}
