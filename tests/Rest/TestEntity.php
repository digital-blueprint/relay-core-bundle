<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest;

use Dbp\Relay\CoreBundle\LocalData\LocalDataAwareInterface;
use Dbp\Relay\CoreBundle\LocalData\LocalDataAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class TestEntity implements LocalDataAwareInterface
{
    use LocalDataAwareTrait;

    /**
     * @Groups({"TestEntity:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"TestEntity:output"})
     *
     * @var string
     */
    private $field0;

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
