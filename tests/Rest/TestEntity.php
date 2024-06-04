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
    private string $identifier;

    #[Groups(['TestEntity:output'])]
    private string $field0;

    public function __construct(string $id)
    {
        $this->identifier = $id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getField0(): string
    {
        return $this->field0;
    }
}
