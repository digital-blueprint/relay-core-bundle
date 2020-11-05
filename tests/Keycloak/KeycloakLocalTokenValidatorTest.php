<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Tests\Keycloak;

use DBP\API\CoreBundle\Keycloak\Keycloak;
use DBP\API\CoreBundle\Keycloak\KeycloakLocalTokenValidator;
use DBP\API\CoreBundle\Keycloak\TokenValidationException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jose\Component\Core\JWK;
use Jose\Easy\Build;
use Jose\Easy\JWSBuilder;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class KeycloakLocalTokenValidatorTest extends TestCase
{
    /* @var KeycloakLocalTokenValidator */
    private $tokenValidator;

    /* @var Keycloak */
    private $keycloak;

    protected function setUp(): void
    {
        $keycloak = new Keycloak('https://auth.example.com/auth', 'tugraz');
        $this->keycloak = $keycloak;
        $cache = new ArrayAdapter();
        $nullLogger = new Logger('dummy', [new NullHandler()]);

        $this->tokenValidator = new KeycloakLocalTokenValidator($keycloak, $cache, $nullLogger);
        $this->mockResponses([]);
    }

    private function getJWK()
    {
        $jwk = new JWK([
            'kty' => 'RSA',
            'kid' => 'bilbo.baggins@hobbiton.example',
            'use' => 'sig',
            'n' => 'n4EPtAOCc9AlkeQHPzHStgAbgs7bTZLwUBZdR8_KuKPEHLd4rHVTeT-O-XV2jRojdNhxJWTDvNd7nqQ0VEiZQHz_AJmSCpMaJMRBSFKrKb2wqVwGU_NsYOYL-QtiWN2lbzcEe6XC0dApr5ydQLrHqkHHig3RBordaZ6Aj-oBHqFEHYpPe7Tpe-OfVfHd1E6cS6M1FZcD1NNLYD5lFHpPI9bTwJlsde3uhGqC0ZCuEHg8lhzwOHrtIQbS0FVbb9k3-tVTU4fg_3L_vniUFAKwuCLqKnS2BYwdq_mzSnbLY7h_qixoR7jig3__kRhuaxwUkRz5iaiQkqgc5gHdrNP5zw',
            'e' => 'AQAB',
            'd' => 'bWUC9B-EFRIo8kpGfh0ZuyGPvMNKvYWNtB_ikiH9k20eT-O1q_I78eiZkpXxXQ0UTEs2LsNRS-8uJbvQ-A1irkwMSMkK1J3XTGgdrhCku9gRldY7sNA_AKZGh-Q661_42rINLRCe8W-nZ34ui_qOfkLnK9QWDDqpaIsA-bMwWWSDFu2MUBYwkHTMEzLYGqOe04noqeq1hExBTHBOBdkMXiuFhUq1BU6l-DqEiWxqg82sXt2h-LMnT3046AOYJoRioz75tSUQfGCshWTBnP5uDjd18kKhyv07lhfSJdrPdM5Plyl21hsFf4L_mHCuoFau7gdsPfHPxxjVOcOpBrQzwQ',
            'p' => '3Slxg_DwTXJcb6095RoXygQCAZ5RnAvZlno1yhHtnUex_fp7AZ_9nRaO7HX_-SFfGQeutao2TDjDAWU4Vupk8rw9JR0AzZ0N2fvuIAmr_WCsmGpeNqQnev1T7IyEsnh8UMt-n5CafhkikzhEsrmndH6LxOrvRJlsPp6Zv8bUq0k',
            'q' => 'uKE2dh-cTf6ERF4k4e_jy78GfPYUIaUyoSSJuBzp3Cubk3OCqs6grT8bR_cu0Dm1MZwWmtdqDyI95HrUeq3MP15vMMON8lHTeZu2lmKvwqW7anV5UzhM1iZ7z4yMkuUwFWoBvyY898EXvRD-hdqRxHlSqAZ192zB3pVFJ0s7pFc',
            'dp' => 'B8PVvXkvJrj2L-GYQ7v3y9r6Kw5g9SahXBwsWUzp19TVlgI-YV85q1NIb1rxQtD-IsXXR3-TanevuRPRt5OBOdiMGQp8pbt26gljYfKU_E9xn-RULHz0-ed9E9gXLKD4VGngpz-PfQ_q29pk5xWHoJp009Qf1HvChixRX59ehik',
            'dq' => 'CLDmDGduhylc9o7r84rEUVn7pzQ6PF83Y-iBZx5NT-TpnOZKF1pErAMVeKzFEl41DlHHqqBLSM0W1sOFbwTxYWZDm6sI6og5iTbwQGIC3gnJKbi_7k_vJgGHwHxgPaX2PnvP-zyEkDERuf-ry4c_Z11Cq9AqC2yeL6kdKT1cYF8',
            'qi' => '3PiqvXQN0zwMeE-sBvZgi289XP9XCQF3VWqPzMKnIgQp7_Tugo6-NZBKCQsMf3HaEGBjTVJs_jcK8-TRXvaKe-7ZMaQj8VfBdYkssbu0NKDDhjJ-GtiseaDVWt7dcH0cfwxgFUHpQh7FoCrjFJ6h6ZEpMF6xmujs4qMpPz8aaI4',
        ]);

        return $jwk;
    }

    private function getPublicJWKs()
    {
        return ['keys' => [$this->getJWK()->toPublic()->jsonSerialize()]];
    }

    private function getJWT(array $options = [])
    {
        $jwk = $this->getJWK();

        $time = $options['time'] ?? time();
        $builder = Build::jws()
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->jti('0123456789')
            ->alg('RS256')
            ->iss($options['issuer'] ?? $this->keycloak->getBaseUrlWithRealm())
            ->aud('audience1')
            ->aud('audience2')
            ->sub('subject');
        assert($builder instanceof JWSBuilder);

        return $builder->sign($jwk);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->tokenValidator->setClientHandler($stack);
    }

    private function mockJWKResponse()
    {
        $jwks = $this->getPublicJWKs();
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($jwks)),
        ]);
    }

    public function testCheckAudienceBad()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        $this->expectExceptionMessageMatches('/Bad audience/');
        KeycloakLocalTokenValidator::checkAudience($result, 'foo');
    }

    public function testCheckAudienceGood()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        KeycloakLocalTokenValidator::checkAudience($result, 'audience2');
        KeycloakLocalTokenValidator::checkAudience($result, 'audience1');
        $this->assertTrue(true);
    }

    public function testLocalNoResponse()
    {
        $this->mockResponses([]);
        $this->expectException(TokenValidationException::class);
        $this->tokenValidator->validate('foobar');
    }

    public function testLocalWrongUrl()
    {
        $this->mockResponses([
            new Response(404, ['Content-Type' => 'application/json']),
        ]);
        $this->expectException(TokenValidationException::class);
        $this->tokenValidator->validate('foobar');
    }

    public function testLocalNoneAlgo()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT();
        $payload = explode('.', $jwt)[1];
        $noneToken = base64_encode('{"alg":"none","typ":"JWT"}').'.'.$payload.'.';
        $this->expectExceptionMessageMatches('/Unsupported algorithm/');
        $this->tokenValidator->validate($noneToken);
    }

    public function testLocalExpired()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(['time' => 42]);
        $this->expectExceptionMessageMatches('/expired/');
        $this->tokenValidator->validate($jwt);
    }

    public function testLocalFutureIssued()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT(['time' => time() + 3600]);
        $this->expectExceptionMessageMatches('/future/');
        $this->tokenValidator->validate($jwt);
    }

    public function testLocalWrongRealm()
    {
        $this->mockJWKResponse();

        $this->expectExceptionMessageMatches('/Unknown issuer/');
        $this->tokenValidator->validate($this->getJWT(['issuer' => 'foobar']));
    }

    public function testLocalInvalidSig()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT();
        $parts = explode('.', $jwt);
        $parts[1] = 'REVBREJFRUY=';

        $this->expectExceptionMessageMatches('/Invalid signature/');
        $this->tokenValidator->validate(implode('.', $parts));
    }

    public function testLocalValid()
    {
        $this->mockJWKResponse();

        $jwt = $this->getJWT();
        $result = $this->tokenValidator->validate($jwt);
        $this->assertEquals('subject', $result['sub']);
    }

    public function testMissingUser()
    {
        $this->mockJWKResponse();
        $result = $this->tokenValidator->validate($this->getJWT());
        $this->assertEquals(null, $result['username']);
    }
}
