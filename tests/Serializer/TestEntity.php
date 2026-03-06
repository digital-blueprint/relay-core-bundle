<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Serializer;

use Dbp\Relay\CoreBundle\Serializer\DateTimeUtcNormalizer;
use Symfony\Component\Serializer\Attribute\Context;

class TestEntity
{
    private \DateTimeImmutable $default;

    #[Context([DateTimeUtcNormalizer::CONTEXT_KEY => true])]
    private \DateTimeImmutable $dateImmutable;

    #[Context([DateTimeUtcNormalizer::CONTEXT_KEY => true])]
    private \DateTime $dateMutable;

    #[Context([DateTimeUtcNormalizer::CONTEXT_KEY => true])]
    private \DateTimeInterface $dateInterface;

    public function getDateImmutable(): \DateTimeImmutable
    {
        return $this->dateImmutable;
    }

    public function setDateImmutable(\DateTimeImmutable $date): void
    {
        $this->dateImmutable = $date;
    }

    public function getDateMutable(): \DateTime
    {
        return $this->dateMutable;
    }

    public function setDateMutable(\DateTime $date): void
    {
        $this->dateMutable = $date;
    }

    public function getDateInterface(): \DateTimeInterface
    {
        return $this->dateInterface;
    }

    public function setDateInterface(\DateTimeInterface $date): void
    {
        $this->dateInterface = $date;
    }

    public function getDefault(): \DateTimeImmutable
    {
        return $this->default;
    }

    public function setDefault(\DateTimeImmutable $default): void
    {
        $this->default = $default;
    }
}
