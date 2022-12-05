<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Event;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationDataMuxer;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This hook can be used to change the value of attributes at the time they are requested.
 *
 * Can be used to change existing attribute values, and introduce new attributes.
 * In case of new attributes you have to make sure to also handle GetAvailableAttributesEvent
 * and register your new attribute there.
 */
class GetAttributeEvent extends Event
{
    /**
     * @var AuthorizationDataMuxer
     */
    private $mux;

    /**
     * @var ?string
     */
    private $userIdentifier;

    /**
     * @var string
     */
    private $attributeName;

    /**
     * @var mixed
     */
    private $attributeValue;

    /**
     * @param mixed $attributeValue
     */
    public function __construct(AuthorizationDataMuxer $mux, string $attributeName, $attributeValue, ?string $userIdentifier)
    {
        $this->mux = $mux;
        $this->userIdentifier = $userIdentifier;
        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
    }

    /**
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getAttribute(string $attributeName, $defaultValue = null)
    {
        return $this->mux->getAttribute($this->userIdentifier, $attributeName, $defaultValue);
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * @param mixed $attributeValue
     */
    public function setAttributeValue($attributeValue): void
    {
        $this->attributeValue = $attributeValue;
    }

    /**
     * @return mixed
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }
}
