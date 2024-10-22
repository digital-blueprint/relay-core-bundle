<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use Symfony\Component\Serializer\Annotation\Groups;

class TestResource
{
    #[Groups(['TestResource:output'])]
    private ?string $identifier = null;

    #[Groups(['TestResource:output'])]
    private ?string $content = null;

    #[Groups(['TestResource:output:admin'])]
    private ?string $secret = null;

    public function getIdentifier(): ?string
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

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): void
    {
        $this->secret = $secret;
    }
}
