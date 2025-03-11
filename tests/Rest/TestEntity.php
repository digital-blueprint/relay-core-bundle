<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\LocalData\LocalDataAwareInterface;
use Dbp\Relay\CoreBundle\LocalData\LocalDataAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class TestEntity implements LocalDataAwareInterface
{
    use LocalDataAwareTrait;

    #[Groups(['TestEntity:output'])]
    private ?string $identifier;

    #[Groups(['TestEntity:output', 'TestEntity:input'])]
    private ?string $field0 = null;

    public function __construct(?string $identifier = null)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getField0(): ?string
    {
        return $this->field0;
    }

    public function setField0(?string $field0): void
    {
        $this->field0 = $field0;
    }
}
