<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @method array getSupportedTypes(?string $format)
 */
class EntityNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED_CONTEXT_KEY = self::class;
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';

    /** @var array<string, callable[]> */
    private array $getOutputGroupsToAddForEntityClassCallbacks = [];
    /** @var array<string, callable[]> */
    private array $getOutputGroupsToAddForEntityInstanceCallbacks = [];

    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @param callable(string): array $getOutputGroupsToAddCallback
     */
    public function registerGetOutputGroupsToAddForEntityClassCallback(string $entityClass,
        callable $getOutputGroupsToAddCallback): void
    {
        Tools::pushToSubarray($this->getOutputGroupsToAddForEntityClassCallbacks,
            $entityClass, $getOutputGroupsToAddCallback);
    }

    public function tryGetGetOutputGroupsToAddForEntityClassCallback(string $entityClass): ?array
    {
        return $this->getOutputGroupsToAddForEntityClassCallbacks[$entityClass] ?? null;
    }

    /**
     * @param callable(object, string): array $getOutputGroupsToAddCallback
     */
    public function registerGetOutputGroupsToAddForEntityInstanceCallback(string $entityClass, callable $getOutputGroupsToAddCallback): void
    {
        Tools::pushToSubarray($this->getOutputGroupsToAddForEntityInstanceCallbacks,
            $entityClass, $getOutputGroupsToAddCallback);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED_CONTEXT_KEY] = true;

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY];

        foreach ($this->getOutputGroupsToAddForEntityInstanceCallbacks[$resourceClass] as $getOutputGroupsToAddForEntityInstanceCallback) {
            $context[self::GROUPS_CONTEXT_KEY] = array_merge($context[self::GROUPS_CONTEXT_KEY] ?? [],
                $getOutputGroupsToAddForEntityInstanceCallback($object, $resourceClass));
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // WORKAROUND: get_class($data) is not safe for the given $data, since it api-platform/doctrine? uses proxy classes
        // for entities, which makes the classname unreliable (instanceof would work, but not with our callback map).
        // Therefore, we are using the resource class from the context here
        // BUT have to exclude paginators which seem to have the resource class of the items that they hold.
        if (isset($context[self::ALREADY_CALLED_CONTEXT_KEY]) || !is_object($data) || $data instanceof PartialPaginatorInterface) {
            return false;
        }

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null;

        return $resourceClass !== null
            && isset($this->getOutputGroupsToAddForEntityInstanceCallbacks[$resourceClass]);
    }
}
