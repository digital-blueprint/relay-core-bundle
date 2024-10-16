<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private const GROUPS_CONTEXT_KEY = 'groups';
    private const RESOURCE_CLASS_CONTEXT_KEY = 'resource_class';
    private array $getNormalizationGroupsToAddCallbacks = [];

    public function __construct(private readonly SerializerContextBuilderInterface $decorated)
    {
    }

    /**
     * @param callable(string): array $getNormalizationGroupsToAddCallback
     */
    public function registerAddNormalizationGroupsCallback(string $entityClass, callable $getNormalizationGroupsToAddCallback): void
    {
        $this->getNormalizationGroupsToAddCallbacks[$entityClass] = $getNormalizationGroupsToAddCallback;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context[self::RESOURCE_CLASS_CONTEXT_KEY] ?? null;

        if (null !== $resourceClass && isset($context[self::GROUPS_CONTEXT_KEY])) {
            if ($normalization
                && ($getNormalizationGroupsCallback = $this->getNormalizationGroupsToAddCallback[$resourceClass] ?? null)) {
                $context[self::GROUPS_CONTEXT_KEY] = array_merge($context[self::GROUPS_CONTEXT_KEY],
                    $getNormalizationGroupsCallback($resourceClass));
            }
        }
        // TODO: denormalization

        return $context;
    }
}
