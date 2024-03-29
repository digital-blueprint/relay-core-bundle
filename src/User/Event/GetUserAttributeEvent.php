<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\Event;

use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This hook can be used to change the value of attributes at the time they are requested.
 *
 * Can be used to change existing attribute values, and introduce new attributes.
 * In case of new attributes you have to make sure to also handle GetAvailableAttributesEvent
 * and register your new attribute there.
 */
class GetUserAttributeEvent extends Event
{
    /**
     * @var UserAttributeMuxer
     */
    private $userAttributeMuxer;

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
    public function __construct(UserAttributeMuxer $userAttributeMuxer, string $attributeName, $attributeValue, ?string $userIdentifier)
    {
        $this->userAttributeMuxer = $userAttributeMuxer;
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
        return $this->userAttributeMuxer->getAttribute($this->userIdentifier, $attributeName, $defaultValue);
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
