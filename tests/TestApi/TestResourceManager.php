<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use Dbp\Relay\CoreBundle\Tests\Kernel;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Dbp\Relay\CoreBundle\TestUtils\TestEntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Uid\Uuid;

class TestResourceManager
{
    public const CONTENT_DEFAULT = 'content';
    public const IS_PUBLIC_DEFAULT = false;
    public const SECRET_DEFAULT = 'secret';
    public const PASSWORD_DEFAULT = 'password';

    private readonly TestEntityManager $testEntityManager;

    public function __construct(ContainerInterface $container)
    {
        $this->testEntityManager = TestEntityManager::create($container, Kernel::TEST_ENTITY_MANAGER_ID);
    }

    public function addTestResource(string $content = self::CONTENT_DEFAULT, bool $isPublic = self::IS_PUBLIC_DEFAULT,
        ?string $secret = self::SECRET_DEFAULT): TestResource
    {
        $testResource = new TestResource();
        $testResource->setIdentifier((string) Uuid::v4());
        $testResource->setContent($content);
        $testResource->setIsPublic($isPublic);
        $testResource->setSecret($secret);
        $this->testEntityManager->saveEntity($testResource);

        return $testResource;
    }

    public function removeTestResourceById(string $identifier): void
    {
        $this->testEntityManager->removeEntity($this->getTestResource($identifier));
    }

    public function getTestResource(string $identifier): TestResource
    {
        $testResource = $this->testEntityManager->getEntityByIdentifier($identifier, TestResource::class);
        if ($testResource === null) {
            throw new \RuntimeException('test resource with given identifier not found: '.$identifier);
        }

        return $testResource;
    }

    public function addTestSubResource(TestResource $testResource,
        bool $isPublic = self::IS_PUBLIC_DEFAULT, ?string $password = self::PASSWORD_DEFAULT): TestSubResource
    {
        $testSubResource = new TestSubResource();
        $testSubResource->setIdentifier((string) Uuid::v4());
        $testSubResource->setTestResource($testResource);
        $testSubResource->setIsPublic($isPublic);
        $testSubResource->setPassword($password);
        $this->testEntityManager->saveEntity($testSubResource);

        return $testSubResource;
    }

    public function removeTestSubResourceById(string $identifier): void
    {
        $this->testEntityManager->removeEntity($this->getTestSubResource($identifier));
    }

    public function getTestSubResource(string $identifier): TestSubResource
    {
        $testResource = $this->testEntityManager->getEntityByIdentifier($identifier, TestSubResource::class);
        if ($testResource === null) {
            throw new \RuntimeException('test sub resource with given identifier not found: '.$identifier);
        }

        return $testResource;
    }
}
