<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\Event;

use Dbp\Relay\CoreBundle\User\UserAttributeException;
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
    private UserAttributeMuxer $userAttributeMuxer;
    private ?string $userIdentifier;
    private string $attributeName;
    private mixed $attributeValue;

    public function __construct(UserAttributeMuxer $userAttributeMuxer, string $attributeName, mixed $attributeValue, ?string $userIdentifier)
    {
        $this->userAttributeMuxer = $userAttributeMuxer;
        $this->userIdentifier = $userIdentifier;
        $this->attributeName = $attributeName;
        $this->attributeValue = $attributeValue;
    }

    /**
     * @throws UserAttributeException
     */
    public function getAttribute(string $attributeName, mixed $defaultValue = null): mixed
    {
        return $this->userAttributeMuxer->getAttribute($this->userIdentifier, $attributeName, $defaultValue);
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    public function setAttributeValue(mixed $attributeValue): void
    {
        $this->attributeValue = $attributeValue;
    }

    public function getAttributeValue(): mixed
    {
        return $this->attributeValue;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }
}
