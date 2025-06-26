<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This hook can be used to add attributes to the set of available attributes.
 */
class GetAvailableUserAttributesEvent extends Event
{
    /**
     * @var string[]
     */
    private array $attributes = [];

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
