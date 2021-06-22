<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Keycloak\Keycloak;
use DBP\API\CoreBundle\Keycloak\KeycloakRemoteTokenValidator;
use DBP\API\CoreBundle\Keycloak\TokenValidationException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class KeycloakRemoteTokenValidatorTest extends TestCase
{
    /* @var KeycloakRemoteTokenValidator */
    private $tokenValidator;

    protected function setUp(): void
    {
        $keycloak = new Keycloak('https://auth.example.com/auth', 'tugraz', 'client', 'secret');

        $this->tokenValidator = new KeycloakRemoteTokenValidator($keycloak);
        $this->mockResponses([]);
    }

    private function getJWT()
    {
        return 'eyJhbGciOiJSUzI1NiJ9.eyJleHAiOjE1OTc3NjM4NTYsImlhdCI6MTU5Nzc2MDI1NiwibmJmIjoxNTk3NzYwMjU2LCJqdGkiOiIwMTIzNDU2Nzg5IiwiaXNzIjoiaHR0cHM6Ly9hdXRoLmV4YW1wbGUuY29tL2F1dGgvcmVhbG1zL3R1Z3JheiIsImF1ZCI6WyJhdWRpZW5jZTEiLCJhdWRpZW5jZTIiXSwic3ViIjoic3ViamVjdCJ9.dlL8Ho0VI_isr3MaOpbRM__l35YURlK16V3bqjZnWizvXUwnQxAEXY-ToGynWzy4LvaCT52aeEE4sxhiFtLvjkeT--l9uojobst23NdAv8csDdtt2kYokPmAoKFnF-97vLQk0YwYeozhttIPlSEFptuT2-8tmbqFaT3LNzzfHIhotgVbZ-vCa7_IAwHj7DcVN_uhPgNb5axk7_pla57dTKIPxu0DAAKMFlMkZbIUfuI8HVFMfpghwH4KfVariQ4OznBBFeacjpz3FMUb5ku2CVVVMS0bN5L9J_EtYw9Umb_ArxeorJhpBAaHGIbfYo02dIRSCuuF1-zvXAkr10j-3g';
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->tokenValidator->setClientHandler($stack);
    }

    public function testValidateOK()
    {
        $result = [
            'exp' => 1597763949,
            'iat' => 1597760349,
            'nbf' => 1597760349,
            'jti' => '0123456789',
            'iss' => 'https://auth.example.com/auth/realms/tugraz',
            'aud' => [
                0 => 'audience1',
                1 => 'audience2',
            ],
            'sub' => 'subject',
            'username' => null,
            'active' => true,
        ];

        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($result)),
        ]);

        $result = $this->tokenValidator->validate($this->getJWT());
        $this->assertNotEmpty($result);
    }

    public function testValidateFail()
    {
        $result = [
            'exp' => 1597763949,
            'iat' => 1597760349,
            'nbf' => 1597760349,
            'jti' => '0123456789',
            'iss' => 'https://auth.example.com/auth/realms/tugraz',
            'aud' => [
                0 => 'audience1',
                1 => 'audience2',
            ],
            'sub' => 'subject',
            'username' => null,
            'active' => false,
        ];

        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($result)),
        ]);

        $this->expectException(TokenValidationException::class);
        $this->tokenValidator->validate($this->getJWT());
    }
}
