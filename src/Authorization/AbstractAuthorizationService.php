<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

abstract class AbstractAuthorizationService implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /* config array keys */
    private const AUTHORIZATION_ROOT_CONFIG_NODE = 'authorization';
    private const ROLES_CONFIG_NODE = 'roles';
    private const ATTRIBUTES_CONFIG_NODE = 'attributes';
    private const ENTITIES_CONFIG_NODE = 'entities';
    private const ENTITY_READ_ACCESS_CONFIG_NODE = 'read_access';
    private const ENTITY_WRITE_ACCESS_CONFIG_NODE = 'write_access';
    private const ENTITY_CLASS_NAME_CONFIG_NODE = 'class_name';

    /* internal array keys */
    private const ROLES_KEY = 'roles';
    private const ATTRIBUTES_KEY = 'attributes';
    private const ENTITIES_KEY = 'entities';
    private const ENTITY_READ_ACCESS_KEY = 'read_access';
    private const ENTITY_WRITE_ACCESS_KEY = 'write_access';
    private const ENTITY_SHORT_NAME_KEY = 'short_name';
    private const ENTITY_CLASS_NAME_KEY = 'class_name';

    private const ENTITY_READ_ACCESS_ATTRIBUTE_NAMES_KEY = 'read_attribute_names';
    private const ENTITY_OBJECT_ALIAS = 'entity';
    private const CONTEXT_GROUPS_KEY = 'groups';

    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    /** @var array|null */
    private $config;

    /** @var array */
    private $entityClassNameToAttributeNamesMapping;

    public function __construct()
    {
        $this->entityClassNameToAttributeNamesMapping = [];
    }

    /**
     * @required
     */
    public function _injectServices(UserSessionInterface $userSession, AuthorizationDataMuxer $mux)
    {
        $this->userAuthorizationChecker = new AuthorizationExpressionChecker($mux);
        $this->currentAuthorizationUser = new AuthorizationUser($userSession, $this->userAuthorizationChecker);
        $this->loadConfig();
    }

    public function setConfig(array $config)
    {
        $this->config = $config[self::AUTHORIZATION_ROOT_CONFIG_NODE] ?? [];
        $this->loadConfig();
    }

    public function configure(array $roleMapping = [], array $attributeMapping = []): void
    {
        $this->config = [
                self::ROLES_CONFIG_NODE => $roleMapping,
                self::ATTRIBUTES_CONFIG_NODE => $attributeMapping,
        ];
        $this->loadConfig();
    }

    /**
     * @param mixed $object
     *
     * @throws ApiError
     */
    public function denyAccessUnlessIsGranted(string $rightName, $object = null, string $objectAlias = null): void
    {
        if ($this->isGrantedInternal($rightName, $object, $objectAlias) === false) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'access denied. missing right '.$rightName);
        }
    }

    /**
     * @param mixed $object
     */
    public function isGranted(string $expressionName, $object = null, string $objectAlias = null): bool
    {
        return $this->isGrantedInternal($expressionName, $object, $objectAlias);
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->getAttributeInternal($attributeName, $defaultValue);
    }

    /**
     *  {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entityClassName = get_class($object);
        $mapEntry = $this->entityClassNameToAttributeNamesMapping[$entityClassName];
        $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

        foreach ($mapEntry[self::ENTITY_READ_ACCESS_ATTRIBUTE_NAMES_KEY] as $attributeName) {
            $attributeId = self::toAttributeId($entityShortName, $attributeName);
            if ($this->isGranted($attributeId, $object, self::ENTITY_OBJECT_ALIAS)) {
                $context[self::CONTEXT_GROUPS_KEY][] = $attributeId;
            }
        }

        $context[self::getUniqueAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     *  {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if ($this->entityClassNameToAttributeNamesMapping === null || is_object($data) === false) {
            return false;
        }

        $entityClassName = get_class($data);

        // Make sure we're not called twice
        if (isset($context[self::getUniqueAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToAttributeNamesMapping);
    }

    private function loadConfig()
    {
        if ($this->userAuthorizationChecker !== null && $this->config !== null) {
            $roleExpressions = $this->config[self::ROLES_CONFIG_NODE] ?? [];
            $attributeExpressions = $this->config[self::ATTRIBUTES_CONFIG_NODE] ?? [];

            if (isset($this->config[self::ENTITIES_CONFIG_NODE])) {
                $entitiesRoleExpressions = $this->loadEntityConfig($this->config[self::ENTITIES_CONFIG_NODE]);
                $roleExpressions = array_merge($roleExpressions, $entitiesRoleExpressions);
            }

            $this->userAuthorizationChecker->setExpressions($roleExpressions, $attributeExpressions);
        }
    }

    private function loadEntityConfig(array $entitiesConfigNode): array
    {
        $roleExpressions = [];
        foreach ($entitiesConfigNode as $entityShortName => $entityNode) {
            $entityClassName = $entityNode[self::ENTITY_CLASS_NAME_CONFIG_NODE];
            $attributeNames = [];
            foreach ($entityNode[self::ENTITY_READ_ACCESS_CONFIG_NODE] ?? [] as $attributeName => $attributeAuthorizationExpression) {
                $roleExpressions[self::toAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                $attributeNames[] = $attributeName;
            }
            $this->entityClassNameToAttributeNamesMapping[$entityClassName] = [
                self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                self::ENTITY_READ_ACCESS_ATTRIBUTE_NAMES_KEY => $attributeNames,
            ];
        }

        return $roleExpressions;
    }

    private function getAttributeInternal(string $attributeName, $defaultValue = null)
    {
        return $this->userAuthorizationChecker->evalAttributeExpression($this->currentAuthorizationUser, $attributeName, $defaultValue);
    }

    /**
     * @throws AuthorizationException
     */
    private function isGrantedInternal(string $rightName, $object, string $objectAlias = null): bool
    {
        return $this->userAuthorizationChecker->isGranted($this->currentAuthorizationUser, $rightName, $object, $objectAlias);
    }

    /**
     * Create the 'authorization' config node definition with the given config definition.
     */
    public static function getAuthorizationConfigNodeDefinition(array $configDefinition): NodeDefinition
    {
        $treeBuilder = new TreeBuilder(self::AUTHORIZATION_ROOT_CONFIG_NODE);

        $rightsNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ROLES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($configDefinition[self::ROLES_KEY] ?? [] as $roleDefinition) {
            $rightsNodeChildBuilder->scalarNode($roleDefinition[0])
                ->defaultValue($roleDefinition[1] ?? 'false')
                ->info($roleDefinition[2] ?? '')
                ->end();
        }

        $attributesNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ATTRIBUTES_CONFIG_NODE)
            ->addDefaultsIfNotSet()
            ->children();
        foreach ($configDefinition[self::ATTRIBUTES_KEY] ?? [] as $attributeDefinition) {
            $attributesNodeChildBuilder->scalarNode($attributeDefinition[0])
                ->defaultValue($attributeDefinition[1] ?? 'null')
                ->info($attributeDefinition[2] ?? '')
                ->end();
        }

        $entitiesNodeChildBuilder = $treeBuilder->getRootNode()->children()->arrayNode(self::ENTITIES_CONFIG_NODE)
            ->children();
        foreach ($configDefinition[self::ENTITIES_KEY] ?? [] as $entityDefinition) {
            $entityChildBuilder = $entitiesNodeChildBuilder->arrayNode($entityDefinition[self::ENTITY_SHORT_NAME_KEY])
                ->children();
            $entityChildBuilder->scalarNode(self::ENTITY_CLASS_NAME_CONFIG_NODE)
                ->defaultValue($entityDefinition[self::ENTITY_CLASS_NAME_KEY])
                    ->info('The entity class name. There is no need to change the default value.')
                    ->end();
            $entityReadAccessChildBuilder = $entityChildBuilder->arrayNode(self::ENTITY_READ_ACCESS_CONFIG_NODE)
                ->children();
            foreach ($entityDefinition[self::ENTITY_READ_ACCESS_KEY] ?? [] as $attributeName) {
                $entityReadAccessChildBuilder->scalarNode($attributeName)
                    ->defaultValue('false')
                    ->info(sprintf('The conditional reader role expression for attribute \'%s\'.', $attributeName))
                    ->end();
            }

            $entityWriteAccessChildBuilder = $entitiesNodeChildBuilder->arrayNode(self::ENTITY_WRITE_ACCESS_CONFIG_NODE)
                ->children();
            foreach ($entityDefinition[self::ENTITY_WRITE_ACCESS_KEY] ?? [] as $attributeName) {
                $entityWriteAccessChildBuilder->scalarNode($attributeName)
                    ->defaultValue('false')
                    ->info(sprintf('The conditional writer role expression for attribute \'%s\'.', $attributeName))
                    ->end();
            }
        }

        return $treeBuilder->getRootNode();
    }

    public static function configDefinitionCreate(): array
    {
        return [];
    }

    public static function configDefinitionAddRole(array &$configDefinition, string $roleName, string $defaultExpression = 'false', string $info = ''): array
    {
        Tools::pushToSubarray($configDefinition, self::ROLES_KEY, [$roleName, $defaultExpression, $info]);

        return $configDefinition;
    }

    public static function configDefinitionAddAttribute(array &$configDefinition, string $attributeName, string $defaultExpression = 'false', string $info = ''): array
    {
        Tools::pushToSubarray($configDefinition, self::ATTRIBUTES_KEY, [$attributeName, $defaultExpression, $info]);

        return $configDefinition;
    }

    public static function configDefinitionAddEntity(array &$configDefinition, string $entityShortName, string $entityClassName, array $readAttributes = [], array $writeAttributes = []): array
    {
        Tools::pushToSubarray($configDefinition, self::ENTITIES_KEY, [
            self::ENTITY_SHORT_NAME_KEY => $entityShortName,
            self::ENTITY_CLASS_NAME_KEY => $entityClassName,
            self::ENTITY_READ_ACCESS_KEY => $readAttributes,
            self::ENTITY_WRITE_ACCESS_KEY => $writeAttributes,
            ]);

        return $configDefinition;
    }

    private static function toAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':'.$attributeName;
    }

    private static function getUniqueAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.$entityClassName;
    }
}
