<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

    /**
     * For testing purposes.
     * NOTE: Must be reset manually, otherwise we lose the registered callbacks on Symfony's pre-request reset.
     */
    public function reset(): void
    {
        $this->getOutputGroupsToAddForEntityClassCallbacks = [];
        $this->getOutputGroupsToAddForEntityInstanceCallbacks = [];
    }

    /**
     * Adds output groups for the given entity class if the given condition is met.
     *
     * @param ?callable(string): bool $condition Condition for the groups to be added. If null, the groups are always added.
     */
    public function showOutputGroupsForEntityClassIf(string $entityClass, array $outputGroups, ?callable $condition = null): void
    {
        $this->registerGetOutputGroupsToAddForEntityClassCallback($entityClass,
            function (string $entityClass) use ($condition, $outputGroups) {
                return $condition === null || $condition($entityClass) ? $outputGroups : [];
            });
    }

    /**
     * Adds output groups for the given entity class if the given roles are granted.
     */
    public function showOutputGroupsForEntityClassIfGrantedRoles(string $entityClass, array $outputGroups,
        array $requiredRoles, AbstractAuthorizationService $authorizationService): void
    {
        $this->registerGetOutputGroupsToAddForEntityClassCallback($entityClass,
            function () use ($requiredRoles, $outputGroups, $authorizationService) {
                foreach ($requiredRoles as $requiredRole) {
                    if (!$authorizationService->isGrantedRole($requiredRole)) {
                        return [];
                    }
                }

                return $outputGroups;
            });
    }

    /**
     * Adds output groups for the given entity instance if the given condition is met.
     *
     * @param callable(object, string): bool $condition
     */
    public function showOutputGroupsForEntityInstanceIf(string $entityClass, array $outputGroups, callable $condition): void
    {
        $this->registerGetOutputGroupsToAddForEntityInstanceCallback($entityClass,
            function (object $entityInstance, string $entityClass) use ($condition, $outputGroups) {
                return $condition($entityInstance, $entityClass) ? $outputGroups : [];
            });
    }

    public function showOutputGroupsForEntityInstanceIfGrantedResourcePermissions(string $entityClass, array $outputGroups,
        array $requiredResourcePermissions, AbstractAuthorizationService $authorizationService): void
    {
        $this->registerGetOutputGroupsToAddForEntityInstanceCallback($entityClass,
            function (object $entityInstance) use ($requiredResourcePermissions, $outputGroups, $authorizationService) {
                foreach ($requiredResourcePermissions as $requiredResourcePermission) {
                    if (!$authorizationService->isGrantedResourcePermission($requiredResourcePermission, $entityInstance)) {
                        return [];
                    }
                }

                return $outputGroups;
            });
    }

    public function tryGetGetOutputGroupsToAddForEntityClassCallback(string $entityClass): ?array
    {
        return $this->getOutputGroupsToAddForEntityClassCallbacks[$entityClass] ?? null;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): string|float|int|bool|\ArrayObject|array|null
    {
        $this->setAlreadyNormalized($object, $context);

        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY];
        foreach ($this->getOutputGroupsToAddForEntityInstanceCallbacks[$resourceClass] as $getOutputGroupsToAddForEntityInstanceCallback) {
            $context[self::GROUPS_CONTEXT_KEY] = array_merge($context[self::GROUPS_CONTEXT_KEY] ?? [],
                $getOutputGroupsToAddForEntityInstanceCallback($object, $resourceClass));
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // WORKAROUND: get_class($data) is not safe for the given $data, since api-platform/doctrine?
        // (sometimes) uses proxy classes for entities,
        // which makes the classname unreliable (instanceof would work, but not with our callback map).
        // Therefore, we are using the resource class from the context here
        // YET have to exclude paginators which seem to have the resource class of the items that they hold.
        return is_object($data)
            && false === $data instanceof PartialPaginatorInterface
            && isset($this->getOutputGroupsToAddForEntityInstanceCallbacks[$context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null])
            && false === $this->wasAlreadyNormalized($data, $context);
    }

    /**
     * @param callable(string): array $getOutputGroupsToAddCallback
     */
    protected function registerGetOutputGroupsToAddForEntityClassCallback(string $entityClass,
        callable $getOutputGroupsToAddCallback): void
    {
        Tools::pushToSubarray($this->getOutputGroupsToAddForEntityClassCallbacks,
            $entityClass, $getOutputGroupsToAddCallback);
    }

    /**
     * @param callable(object, string): array $getOutputGroupsToAddCallback
     */
    protected function registerGetOutputGroupsToAddForEntityInstanceCallback(string $entityClass, callable $getOutputGroupsToAddCallback): void
    {
        Tools::pushToSubarray($this->getOutputGroupsToAddForEntityInstanceCallbacks,
            $entityClass, $getOutputGroupsToAddCallback);
    }

    private function setAlreadyNormalized(object $entity, array &$context): void
    {
        // we do already called prevention on an object basis, bacause when calling this normalizer only once
        // per root entity (like in the api-platform docs) the normalization
        // of relations (child entities) doesn't work as intented (because they inherit the parent context(s))
        $context[self::ALREADY_CALLED_CONTEXT_KEY][spl_object_hash($entity)] = true;
    }

    private function wasAlreadyNormalized(object $entity, array &$context): bool
    {
        return isset($context[self::ALREADY_CALLED_CONTEXT_KEY][spl_object_hash($entity)]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->normalizer->getSupportedTypes($format);
    }
}
