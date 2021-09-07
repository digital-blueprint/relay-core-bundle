<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use PHPUnit\Framework\TestCase;

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
}
