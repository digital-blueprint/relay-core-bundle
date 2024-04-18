<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use Symfony\Component\Serializer\Annotation\Groups;

class TestResource
{
    #[Groups(['TestResource:output'])]
    private string $identifier;

    #[Groups(['TestResource:output'])]
    private ?string $content;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }
}
