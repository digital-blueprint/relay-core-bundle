<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This hook can be used to change the set of available attributes.
 *
 * You can extend the set, or remove attributes.
 */
class GetAvailableUserAttributesEvent extends Event
{
    /**
     * @param string[] $attributes
     */
    public function __construct(private array $attributes)
    {
    }

    /**
     * @param string[] $attributes
     */
    public function addAttributes(array $attributes): void
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    /**
     * @return string[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
