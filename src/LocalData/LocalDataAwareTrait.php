<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\LocalData;

trait LocalDataAwareTrait
{
    /**
     * @ApiProperty(iri="https://schema.org/additionalProperty")
     * @Groups({"LocalData:output"})
     *
     * @var array
     */
    private $localData;

    /**
     * Returns the array of local data attributes.
     */
    public function getLocalData(): ?array
    {
        return $this->localData;
    }

    public function setLocalData(?array $localData)
    {
        $this->localData = $localData;
    }

    /**
     * Sets the value of a local data attribute.
     *
     * @param string     $key   the attribute name
     * @param mixed|null $value the attribute value
     */
    public function setLocalDataValue(string $key, $value): void
    {
        if (!$this->localData) {
            $this->localData = [];
        }
        $this->localData[$key] = $value;
    }

    /**
     * @Ignore
     * Returns the value of a local data attribute.
     *
     * @param string $key the attribute name
     *
     * @return ?mixed the value or null if the attribute is not found
     */
    public function getLocalDataValue(string $key)
    {
        return $this->localData ? ($this->localData[$key] ?? null) : null;
    }

    /**
     * Returns whether there is a local attribute with the given name.
     *
     * @param string $key The attribute name
     */
    public function hasLocalDataValue(string $key): bool
    {
        return $this->localData && array_key_exists($key, $this->localData);
    }
}
