<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Serializer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class KernelSerializerTest extends ApiTestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->serializer = $container->get(SerializerInterface::class);
    }

    public function testNormalizeEntityDateTime(): void
    {
        $entity = new TestEntity();
        $entity->setDateImmutable(new \DateTimeImmutable('2026-03-09T14:07:19.251+13:00'));
        $entity->setDateMutable(new \DateTime('2026-03-09T14:07:19.251+13:00'));
        $entity->setDateInterface(new \DateTimeImmutable('2026-03-09T14:07:19.251+13:00'));
        $entity->setDefault(new \DateTimeImmutable('2027-03-09T14:07:19.251+13:00'));
        $result = $this->serializer->normalize($entity, 'jsonld', []);
        $this->assertSame('2026-03-09T01:07:19.251Z', $result['dateImmutable']);
        $this->assertSame('2026-03-09T01:07:19.251Z', $result['dateMutable']);
        $this->assertSame('2026-03-09T01:07:19.251Z', $result['dateInterface']);
        $this->assertSame('2027-03-09T14:07:19+13:00', $result['default']);
    }

    public function testDenormalizeEntityDateTime(): void
    {
        $result = $this->serializer->denormalize(
            [
                'dateImmutable' => '2026-03-09T01:07:19.251Z',
                'dateMutable' => '2026-03-09T01:07:19.251Z',
                'dateInterface' => '2026-03-09T01:07:19.251Z',
                'default' => '2027-03-09T14:07:19+13:00',
            ],
            TestEntity::class,
            'jsonld',
            []
        );
        $this->assertInstanceOf(TestEntity::class, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getDateImmutable());
        $this->assertSame(
            '2026-03-09T01:07:19.251+00:00',
            $result->getDateImmutable()->format('Y-m-d\TH:i:s.vP')
        );
        $this->assertInstanceOf(\DateTime::class, $result->getDateMutable());
        $this->assertSame(
            '2026-03-09T01:07:19.251+00:00',
            $result->getDateMutable()->format('Y-m-d\TH:i:s.vP')
        );
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getDateInterface());
        $this->assertSame(
            '2026-03-09T01:07:19.251+00:00',
            $result->getDateInterface()->format('Y-m-d\TH:i:s.vP')
        );
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getDefault());
        $this->assertSame(
            '2027-03-09T14:07:19.000+13:00',
            $result->getDefault()->format('Y-m-d\TH:i:s.vP')
        );
    }
}
