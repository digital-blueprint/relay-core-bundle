<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

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
    public function getLocalData(): array
    {
        return $this->localData;
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
}
