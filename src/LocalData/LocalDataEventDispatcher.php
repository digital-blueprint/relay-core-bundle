<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\AnnotationResourceMetadataFactory;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class LocalDataEventDispatcher
{
    public const SEPARATOR = ',';

    /** @var array */
    private $queryParameters;

    /** @var array */
    private $requestedAttributes;

    /** @var string */
    private $uniqueEntityName;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param string                   $resourceClass    The class name of the entity (resource) this event dispatcher is responsible for
     * @param EventDispatcherInterface $eventDispatcher  The inner event dispatcher that this event dispatcher decorates
     * @param string|null              $uniqueEntityName The unique name of the entity. If not specified or empty, the 'shortName' attribute of the entities @ApiResource annotation is used.
     */
    public function __construct(string $resourceClass, EventDispatcherInterface $eventDispatcher, string $uniqueEntityName = null)
    {
        $this->queryParameters = [];
        $this->requestedAttributes = [];
        $this->uniqueEntityName = !Tools::isNullOrEmpty($uniqueEntityName) ? $uniqueEntityName : self::getUniqueEntityName($resourceClass);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * To be called at the beginning of a new operation.
     */
    public function onNewOperation(array &$options): void
    {
        $this->initIncludeParameters(LocalData::getIncludeParameter($options));
        $this->initQueryParameters(LocalData::getQueryParameter($options));

        LocalData::removeOptions($options);
    }

    /**
     * Returns, whether the attribute with the given name was requested.
     */
    public function isAttributeRequested(string $attributeName): bool
    {
        return in_array($attributeName, $this->requestedAttributes, true);
    }

    /**
     * Checks if the given entity's local data attribute names matches the list of requested attributes this event dispatcher's entity (resource).
     * NOTE: The resource class of the entities must match.
     *
     * @param LocalDataAwareInterface $entity The entity whose local data attributes to check
     */
    public function checkRequestedAttributesIdentical(LocalDataAwareInterface $entity)
    {
        assert(self::getUniqueEntityName(get_class($entity)) === $this->uniqueEntityName);

        $availableAttributes = $entity->getLocalData() ? array_keys($entity->getLocalData()) : [];

        return count($this->requestedAttributes) === count($availableAttributes) &&
            empty(array_diff($this->requestedAttributes, $availableAttributes));
    }

    /**
     * Dispatches the given event.
     */
    public function dispatch(Event $event, string $eventName = null): void
    {
        if ($event instanceof LocalDataPreEvent) {
            $event->setQueryParameters($this->queryParameters);
            $this->eventDispatcher->dispatch($event, $eventName);
        } elseif ($event instanceof LocalDataPostEvent) {
            $event->setRequestedAttributes($this->requestedAttributes);
            $this->eventDispatcher->dispatch($event, $eventName);

            $remainingLocalDataAttributes = $event->getRemainingRequestedAttributes();
            if (!empty($remainingLocalDataAttributes)) {
                throw ApiError::withDetails(Response::HTTP_BAD_REQUEST, sprintf("the following requested local data attributes could not be provided for resource '%s': %s", $this->uniqueEntityName, implode(', ', $remainingLocalDataAttributes)));
            }
        } else {
            $this->eventDispatcher->dispatch($event, $eventName);
        }
    }

    /**
     * Returns the unique API resource name, i.e. short name, of this entity. For this to work, the ApiResource annotation
     * of the entity has to include a non-empty 'shortName' attribute.
     *
     * @throws ApiError if the ApiResource annotation of $resourceClass doesn't have a non-empty 'shortName' attribute
     */
    public static function getUniqueEntityName(string $resourceClass): string
    {
        $resourceMetadataFactory = new AnnotationResourceMetadataFactory(new AnnotationReader());
        try {
            $resourceMetadata = $resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $exc) {
            throw new ApiError(500, $exc->getMessage());
        }

        $uniqueName = $resourceMetadata->getShortName() ?? '';
        if (Tools::isNullOrEmpty($uniqueName)) {
            throw new ApiError(500, sprintf("'shortName' attribute missing in ApiResource annotation of resource class '%s'", $resourceClass));
        } elseif (str_contains($uniqueName, '.') || str_contains($uniqueName, ',')) {
            throw new ApiError(500, sprintf("'shortName' attribute of resource class '%s' must not contain '.' or ',' characters: '%s'", $resourceClass, $uniqueName));
        }

        return $uniqueName;
    }

    /**
     * Parses the local data request parameter and extracts the list of requested attributes for this event dispatcher's entity (resource).
     *
     * @param ?string $includeParameter The value of the 'include' parameter as passed to a GET-operation
     */
    private function initIncludeParameters(?string $includeParameter): void
    {
        $this->requestedAttributes = [];

        if (!Tools::isNullOrEmpty($includeParameter)) {
            $requestedAttributes = explode(self::SEPARATOR, $includeParameter);

            foreach ($requestedAttributes as $requestedAttribute) {
                $requestedAttribute = trim($requestedAttribute);
                if ($requestedAttribute !== '') {
                    $uniqueEntityName = null;
                    $uniqueAttributeName = null;
                    if (!$this->parseLocalDataAttribute($requestedAttribute, $uniqueEntityName, $uniqueAttributeName)) {
                        throw new ApiError(400, sprintf("value of '%s' parameter has invalid format: '%s' (Example: 'attr,ResourceName.attr2')", LocalData::INCLUDE_PARAMETER_NAME, $requestedAttribute));
                    }

                    if ($this->uniqueEntityName === $uniqueEntityName) {
                        $this->requestedAttributes[] = $uniqueAttributeName;
                    }
                }
            }
            $this->requestedAttributes = array_unique($this->requestedAttributes);
        }
    }

    private function initQueryParameters(?string $queryParameter)
    {
        $this->queryParameters = [];

        if (!Tools::isNullOrEmpty($queryParameter)) {
            $localQueryParameters = explode(self::SEPARATOR, $queryParameter);

            foreach ($localQueryParameters as $localQueryParameter) {
                $localQueryParameter = trim($localQueryParameter);
                if ($localQueryParameter !== '') {
                    $parameterKey = null;
                    $parameterValue = null;
                    $uniqueEntityName = null;
                    $uniqueAttributeName = null;
                    if (!$this->parseQueryParameterAssignment($localQueryParameter, $parameterKey, $parameterValue) ||
                        !$this->parseLocalDataAttribute($parameterKey ?? '', $uniqueEntityName, $uniqueAttributeName)) {
                        throw new ApiError(400, sprintf("'%s' parameter has invalid format: '%s' (Example: 'param1:val1,ResourceName.attr1:val2')", LocalData::QUERY_PARAMETER_NAME, $localQueryParameter));
                    }

                    if ($uniqueEntityName === $this->uniqueEntityName) {
                        $this->queryParameters[$parameterKey] = $parameterValue;
                    }
                }
            }
        }
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private function parseQueryParameterAssignment(string $parameterAssignment, ?string &$parameter, ?string &$value): bool
    {
        $parameter = null;
        $value = null;

        $parts = explode(':', $parameterAssignment);

        if (count($parts) === 2) {
            $parameter = $parts[0];
            $value = $parts[1];
        }

        return !Tools::isNullOrEmpty($parameter) && !Tools::isNullOrEmpty($value);
    }

    /**
     * Parses a local data attribute of the form 'UniqueEntityName.attributeName'.
     * NOTE: Due to possible performance impact, there is currently no regex check for valid entity and attribute names (i.e. PHP type/variable names).
     *
     * @retrun true if $localDataAttribute complies with the local attribute format, false otherwise
     */
    private function parseLocalDataAttribute(string $localDataAttribute, ?string &$uniqueEntityName, ?string &$attributeName): bool
    {
        $uniqueEntityName = null;
        $attributeName = null;

        $parts = explode('.', $localDataAttribute);
        if (count($parts) === 1) {
            $uniqueEntityName = $this->uniqueEntityName;
            $attributeName = $parts[0];
        } elseif (count($parts) === 2) {
            $uniqueEntityName = $parts[0];
            $attributeName = $parts[1];
        }

        return !Tools::isNullOrEmpty($uniqueEntityName) && !Tools::isNullOrEmpty($attributeName);
    }
}
