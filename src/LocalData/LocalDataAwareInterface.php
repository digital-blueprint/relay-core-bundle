<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

interface LocalDataAwareInterface
{
    public function setLocalData(?array $localData): void;

    /**
     * Returns the array of local data attributes.
     */
    public function getLocalData(): ?array;

    /**
     * Sets the value of a local data attribute.
     *
     * @param string $key   the attribute name
     * @param ?mixed $value the attribute value
     */
    public function setLocalDataValue(string $key, $value): void;

    /**
     * Returns the value of a local data attribute.
     *
     * @param string $key The attribute name
     *
     * @return ?mixed The value or null if there is no local data attribute with the given name
     */
    public function getLocalDataValue(string $key);

    /**
     * Returns whether there is a local attribute with the given name.
     *
     * @param string $key The attribute name
     */
    public function hasLocalDataValue(string $key): bool;
}
