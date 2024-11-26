<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Table(name: 'test_resources')]
#[ORM\Entity]
class TestResource
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['TestResource:input', 'TestResource:output', 'TestSubResource:output'])]
    private ?string $identifier = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['TestResource:input', 'TestResource:output'])]
    private ?string $content = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['TestResource:input', 'TestResource:output'])]
    private ?int $number = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['TestResource:input', 'TestResource:output', 'TestSubResource:output'])]
    private bool $isPublic = false;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    #[Groups(['TestResource:input', 'TestResource:output:admin'])]
    private ?string $secret = null;

    #[ORM\OneToMany(targetEntity: TestSubResource::class, mappedBy: 'testResource')]
    #[Groups(['TestResource:output'])]
    private Collection $subResources;

    public static function createTestResource(): TestResource
    {
        $resource = new TestResource();
        $resource->setIdentifier((string) Uuid::v4());
        $resource->setContent('This is a test resource.');
        $resource->setIsPublic(true);
        $resource->setSecret('test-secret');

        return $resource;
    }

    public function __construct()
    {
        $this->subResources = new ArrayCollection();
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): void
    {
        $this->number = $number;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): void
    {
        $this->secret = $secret;
    }

    public function getSubResources(): Collection
    {
        return $this->subResources;
    }

    public function setSubResources(Collection $subResources): void
    {
        $this->subResources = $subResources;
    }
}
