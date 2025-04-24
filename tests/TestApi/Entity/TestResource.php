<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceItemController;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestResourceProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/test/test-resources/{identifier}',
            provider: TestResourceProvider::class
        ),
        new GetCollection(
            uriTemplate: '/test/test-resources',
            provider: TestResourceProvider::class
        ),
        new Post(
            uriTemplate: '/test/test-resources',
            processor: TestResourceProcessor::class
        ),
        new Delete(
            uriTemplate: '/test/test-resources/{identifier}',
            provider: TestResourceProvider::class,
            processor: TestResourceProcessor::class
        ),
        new Get(
            uriTemplate: '/test/test-resources/{identifier}/custom_controller_json',
            formats: [
                'json' => ['application/json'],
            ],
            controller: TestResourceItemController::class,
            read: false,
            name: 'custom_controller_get_json'
        ),
        new Get(
            uriTemplate: '/test/test-resources/{identifier}/custom_controller',
            controller: TestResourceItemController::class,
            read: false,
            name: 'custom_controller_get_default'
        ),
    ],
    normalizationContext: [
        'groups' => ['TestResource:output'],
    ],
    denormalizationContext: [
        'groups' => ['TestResource:input'],
    ]
)]
#[ORM\Table(name: 'test_resources')]
#[ORM\Entity]
class TestResource
{
    #[ApiProperty(identifier: true)]
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
    private ?UploadedFile $file = null;

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

    public function setFile(UploadedFile $file): void
    {
        $this->file = $file;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }
}
