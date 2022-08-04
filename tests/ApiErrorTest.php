<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Serializer\ApiErrorNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

class ApiErrorTest extends TestCase
{
    public function testBasics()
    {
        $error = new ApiError(400, 'foobar');
        $message = json_decode($error->getMessage(), true);
        $this->assertSame('foobar', $message['message']);
        $this->assertSame(400, $error->getStatusCode());
    }

    public function testWithDetails()
    {
        $error = ApiError::withDetails(424, 'message', 'id', ['foo' => 'bar']);
        $message = json_decode($error->getMessage(), true);
        $this->assertSame('message', $message['message']);
        $this->assertSame('id', $message['errorId']);
        $this->assertSame(['foo' => 'bar'], $message['errorDetails']);
        $this->assertSame(424, $error->getStatusCode());
    }

    private function normalize(ApiError $error, string $format): array
    {
        $norm = new ApiErrorNormalizer();
        $norm->setNormalizer(new ProblemNormalizer());
        $exc = FlattenException::createFromThrowable($error, $error->getStatusCode());

        return $norm->normalize($exc, $format);
    }

    public function testNormalizer()
    {
        $error = ApiError::withDetails(424, 'message', 'id', ['foo' => 'bar']);
        $res = self::normalize($error, 'jsonld');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['hydra:description'], 'message');
        $this->assertSame($res['relay:errorId'], 'id');
        $this->assertSame($res['relay:errorDetails'], ['foo' => 'bar']);

        $res = self::normalize($error, 'jsonproblem');
        $this->assertSame($res['status'], 424);
        $this->assertSame($res['detail'], 'message');
        $this->assertSame($res['errorId'], 'id');
        $this->assertSame($res['errorDetails'], ['foo' => 'bar']);
    }
}
