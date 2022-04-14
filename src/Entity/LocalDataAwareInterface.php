<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Entity;

interface LocalDataAwareInterface
{
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
     * @return ?mixed The value or null if the attribute is not found
     */
    public function getLocalDataValue(string $key);
}
