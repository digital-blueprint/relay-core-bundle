<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

abstract class AbstractAuthorizationService implements ContextAwareNormalizerInterface, NormalizerAwareInterface, ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    /* internal array keys */
    private const ENTITY_SHORT_NAME_KEY = 'short_name';
    private const ENTITY_CLASS_NAME_KEY = 'class_name';

    private const ENTITY_ATTRIBUTE_NAMES_KEY = 'attribute_names';
    private const ENTITY_OBJECT_ALIAS = 'entity';
    private const CONTEXT_GROUPS_KEY = 'groups';

    /** @var AuthorizationExpressionChecker */
    private $userAuthorizationChecker;

    /** @var AuthorizationUser */
    private $currentAuthorizationUser;

    /** @var array|null */
    private $config;

    /** @var array */
    private $entityClassNameToReadAttributeNamesMapping;

    /** @var array */
    private $entityClassNameToWriteAttributeNamesMapping;

    public function __construct()
    {
        $this->entityClassNameToReadAttributeNamesMapping = [];
        $this->entityClassNameToWriteAttributeNamesMapping = [];
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

    /**
     * Method for bundle config injection. Don't call in your code  (use @see AbstractAuthorizationService::configure()).
     */
    public function setConfig(array $config)
    {
        $this->config = $config[AuthorizationConfigDefinition::AUTHORIZATION_CONFIG_NODE] ?? [];

        $this->loadConfig();
    }

    public function configure(array $roles = [], array $attributes = [], array $entities = []): void
    {
        $this->config = [
            AuthorizationConfigDefinition::ROLES_CONFIG_NODE => $roles,
            AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE => $attributes,
            AuthorizationConfigDefinition::ENTITIES_CONFIG_NODE => $entities,
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
     * {@inheritdoc}
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entityClassName = get_class($object);
        $mapEntry = $this->entityClassNameToReadAttributeNamesMapping[$entityClassName];
        $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

        foreach ($mapEntry[self::ENTITY_ATTRIBUTE_NAMES_KEY] as $attributeName) {
            $attributeId = self::toReadAttributeId($entityShortName, $attributeName);
            if ($this->isGranted($attributeId, $object, self::ENTITY_OBJECT_ALIAS)) {
                $context[self::CONTEXT_GROUPS_KEY][] = $attributeId;
            }
        }

        $context[self::getUniqueNormalizerAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     *  {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if ($this->entityClassNameToReadAttributeNamesMapping === null || is_object($data) === false) {
            return false;
        }

        $entityClassName = get_class($data);

        // Make sure we're not called twice
        if (isset($context[self::getUniqueNormalizerAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToReadAttributeNamesMapping);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $entityClassName = $type;
        $mapEntry = $this->entityClassNameToReadAttributeNamesMapping[$entityClassName];
        $entityShortName = $mapEntry[self::ENTITY_SHORT_NAME_KEY];

        foreach ($mapEntry[self::ENTITY_ATTRIBUTE_NAMES_KEY] as $attributeName) {
            $attributeId = self::toWriteAttributeId($entityShortName, $attributeName);
            if ($this->isGranted($attributeId, $data, self::ENTITY_OBJECT_ALIAS)) {
                $context[self::CONTEXT_GROUPS_KEY][] = $attributeId;
            }
        }

        $context[self::getUniqueDenormalizerAlreadyCalledKeyForEntity($entityClassName)] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     *  {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if ($this->entityClassNameToWriteAttributeNamesMapping === null) {
            return false;
        }

        $entityClassName = $type;

        // Make sure we're not called twice
        if (isset($context[self::getUniqueDenormalizerAlreadyCalledKeyForEntity($entityClassName)])) {
            return false;
        }

        return array_key_exists($entityClassName, $this->entityClassNameToWriteAttributeNamesMapping);
    }

    private function loadConfig()
    {
        if ($this->userAuthorizationChecker !== null && $this->config !== null) {
            $roleExpressions = $this->config[AuthorizationConfigDefinition::ROLES_CONFIG_NODE] ?? [];
            $attributeExpressions = $this->config[AuthorizationConfigDefinition::ATTRIBUTES_CONFIG_NODE] ?? [];

            if (isset($this->config[AuthorizationConfigDefinition::ENTITIES_CONFIG_NODE])) {
                $entitiesRoleExpressions = $this->loadEntityConfig($this->config[AuthorizationConfigDefinition::ENTITIES_CONFIG_NODE]);
                $roleExpressions = array_merge($roleExpressions, $entitiesRoleExpressions);
            }

            $this->userAuthorizationChecker->setExpressions($roleExpressions, $attributeExpressions);
        }
    }

    private function loadEntityConfig(array $entitiesConfigNode): array
    {
        $roleExpressions = [];
        foreach ($entitiesConfigNode as $entityShortName => $entityNode) {
            $entityClassName = $entityNode[AuthorizationConfigDefinition::ENTITY_CLASS_NAME_CONFIG_NODE];
            $attributeNames = [];

            foreach ($entityNode[AuthorizationConfigDefinition::ENTITY_READ_ACCESS_CONFIG_NODE] ?? [] as $attributeName => $attributeAuthorizationExpression) {
                $roleExpressions[self::toReadAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                $attributeNames[] = $attributeName;
            }
            $this->entityClassNameToReadAttributeNamesMapping[$entityClassName] = [
                self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                self::ENTITY_ATTRIBUTE_NAMES_KEY => $attributeNames,
            ];

            foreach ($entityNode[AuthorizationConfigDefinition::ENTITY_WRITE_ACCESS_CONFIG_NODE] ?? [] as $attributeName => $attributeAuthorizationExpression) {
                $roleExpressions[self::toWriteAttributeId($entityShortName, $attributeName)] = $attributeAuthorizationExpression;
                $attributeNames[] = $attributeName;
            }
            $this->entityClassNameToWriteAttributeNamesMapping[$entityClassName] = [
                self::ENTITY_SHORT_NAME_KEY => $entityShortName,
                self::ENTITY_ATTRIBUTE_NAMES_KEY => $attributeNames,
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

    private static function toReadAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':output:'.$attributeName;
    }

    private static function toWriteAttributeId(string $entityShortName, string $attributeName): string
    {
        return $entityShortName.':input:'.$attributeName;
    }

    private static function getUniqueNormalizerAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.'.normalize.'.$entityClassName;
    }

    private static function getUniqueDenormalizerAlreadyCalledKeyForEntity(string $entityClassName): string
    {
        return self::class.'.denormalizer.'.$entityClassName;
    }
}
