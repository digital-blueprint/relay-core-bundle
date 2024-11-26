<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Service;

use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestSubResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class TestResourceService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function addTestResource(TestResource $testResource): TestResource
    {
        $testResource->setIdentifier((string) Uuid::v7());

        $this->entityManager->persist($testResource);
        $this->entityManager->flush();

        return $testResource;
    }

    public function getTestResource(string $identifier): ?TestResource
    {
        return $this->entityManager->getRepository(TestResource::class)->findOneBy(['identifier' => $identifier]);
    }

    public function getTestResources(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = []): array
    {
        // TODO
        return [];
    }

    public function removeTestResource(TestResource $testResource): void
    {
        $this->entityManager->remove($testResource);
        $this->entityManager->flush();
    }

    public function addTestSubResource(TestSubResource $testSubResource): TestSubResource
    {
        $testSubResource->setIdentifier((string) Uuid::v7());

        $this->entityManager->persist($testSubResource);
        $this->entityManager->flush();

        return $testSubResource;
    }

    public function removeTestSubResource(TestSubResource $testSubResource): void
    {
        $this->entityManager->remove($testSubResource);
        $this->entityManager->flush();
    }

    public function getTestSubResource(string $identifier): ?TestSubResource
    {
        return $this->entityManager->getRepository(TestSubResource::class)->findOneBy(['identifier' => $identifier]);
    }

    public function getTestSubResources(int $currentPageNumber, int $maxNumItemsPerPage, array $filters = []): array
    {
        // TODO
        return [];
    }
}
