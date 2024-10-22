<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';

    public function __construct(
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly EntityNormalizer $entityNormalizer)
    {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null;

        if (null !== $resourceClass) {
            if ($normalization
                && ($getOutputGroupsToAddCallbacks =
                    $this->entityNormalizer->tryGetGetOutputGroupsToAddForEntityClassCallback($resourceClass))) {
                foreach ($getOutputGroupsToAddCallbacks as $getOutputGroupsToAddCallback) {
                    $context[self::GROUPS_CONTEXT_KEY] = array_merge($context[self::GROUPS_CONTEXT_KEY] ?? [],
                        $getOutputGroupsToAddCallback($resourceClass));
                }
            }
        }

        return $context;
    }
}
