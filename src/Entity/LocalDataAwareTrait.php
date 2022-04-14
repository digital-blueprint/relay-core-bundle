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

    public function getLocalData(): array
    {
        return $this->localData;
    }

    /**
     * Adds a local data entry.
     *
     * @param mixed|null $value
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
     * Returns the local data value for the given key or null if the key is not found.
     *
     * @return ?mixed
     */
    public function getLocalDataValue(string $key)
    {
        return $this->localData ? ($this->localData[$key] ?? null) : null;
    }
}
