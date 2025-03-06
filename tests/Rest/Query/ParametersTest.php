<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Rest\Query;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Parameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ParametersTest extends TestCase
{
    public function testGetBool(): void
    {
        $parameters = [
            'fooTrue' => 'true',
            'foo1' => '1',
            'fooFalse' => 'false',
            'foo0' => '0',
            'fooInvalid' => 'invalid',
        ];
        self::assertTrue(Parameters::getBool($parameters, 'fooTrue', true));
        self::assertTrue(Parameters::getBool($parameters, 'fooTrue', false));
        self::assertTrue(Parameters::getBool($parameters, 'foo1'));

        self::assertFalse(Parameters::getBool($parameters, 'fooFalse', true));
        self::assertFalse(Parameters::getBool($parameters, 'fooFalse', false));
        self::assertFalse(Parameters::getBool($parameters, 'foo0'));

        self::assertTrue(Parameters::getBool($parameters, 'bar', true));
        self::assertFalse(Parameters::getBool($parameters, 'bar', false));
        self::assertFalse(Parameters::getBool($parameters, 'bar'));

        try {
            Parameters::getBool($parameters, 'bar', throwIfMissing: true);
            self::fail('Expected exception not thrown');
        } catch (ApiError $apiError) {
            self::assertEquals(Response::HTTP_BAD_REQUEST, $apiError->getStatusCode());
        }

        try {
            Parameters::getBool($parameters, 'fooInvalid');
        } catch (ApiError $apiError) {
            self::assertEquals(Response::HTTP_BAD_REQUEST, $apiError->getStatusCode());
        }

        self::assertTrue(Parameters::getBool($parameters, 'fooInvalid', true, throwOnSyntaxError: false));
        self::assertFalse(Parameters::getBool($parameters, 'fooInvalid', false, throwOnSyntaxError: false));
    }
}
