<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\User;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;

class DummyUserAttributeProvider implements UserAttributeProviderInterface
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $available;

    public function __construct(array $attributes, ?array $available = null)
    {
        $this->attributes = $attributes;
        $this->available = $available ?? array_keys($attributes);
    }

    /**
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        return $this->available;
    }

    public function getUserAttributes(?string $userIdentifier): array
    {
        return $this->attributes;
    }
}
