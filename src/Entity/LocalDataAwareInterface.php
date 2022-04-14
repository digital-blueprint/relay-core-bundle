<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Entity;

interface LocalDataAwareInterface
{
    /**
     * Returns the unique name (shortName of the ApiResource) of this entity.
     */
    public static function getUniqueEntityName(): string;

    /**
     * Sets the value of a local data attribute.
     *
     * @param mixed|null $value
     */
    public function setLocalDataValue(string $key, $value): void;

    /**
     * Returns the value of local data value attribute or null if the attribute is not found.
     *
     * @return ?mixed
     */
    public function getLocalDataValue(string $key);
}
