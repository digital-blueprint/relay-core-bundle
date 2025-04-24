<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProcessor;
use Dbp\Relay\CoreBundle\Tests\TestApi\Rest\TestSubResourceProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/test/test-sub-resources/{identifier}',
            provider: TestSubResourceProvider::class
        ),
        new GetCollection(
            uriTemplate: '/test/test-sub-resources',
            provider: TestSubResourceProvider::class
        ),
        new Post(
            uriTemplate: '/test/test-sub-resources',
            processor: TestSubResourceProcessor::class
        ),
        new Delete(
            uriTemplate: '/test/test-sub-resources/{identifier}',
            provider: TestSubResourceProvider::class,
            processor: TestSubResourceProcessor::class
        ),
    ],
    normalizationContext: [
        'groups' => ['TestSubResource:output'],
    ],
    denormalizationContext: [
        'groups' => ['TestSubResource:input'],
    ]
)]
#[ORM\Table(name: 'test_sub_resources')]
#[ORM\Entity]
class TestSubResource
{
    #[ApiProperty(identifier: true)]
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['TestSubResource:input', 'TestSubResource:output', 'TestResource:output'])]
    private ?string $identifier = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['TestSubResource:input', 'TestSubResource:output', 'TestResource:output'])]
    private bool $isPublic = false;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    #[Groups(['TestSubResource:input', 'TestSubResource:output:admin'])]
    private ?string $password = null;

    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    #[ORM\ManyToOne(targetEntity: TestResource::class, inversedBy: 'subResources')]
    #[Groups(['TestSubResource:output'])]
    private ?TestResource $testResource = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getTestResource(): ?TestResource
    {
        return $this->testResource;
    }

    public function setTestResource(?TestResource $testResource): void
    {
        $this->testResource = $testResource;
    }
}
