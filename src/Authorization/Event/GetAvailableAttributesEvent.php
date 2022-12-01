<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This hook can be used to change the set of available attributes.
 *
 * You can extend the set, or remove attributes.
 */
class GetAvailableAttributesEvent extends Event
{
    /**
     * @var string[]
     */
    private $attributes;

    /**
     * @param string[] $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return string[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string[] $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function addAttribute(string $name): void
    {
        $this->attributes[] = $name;
    }
}
