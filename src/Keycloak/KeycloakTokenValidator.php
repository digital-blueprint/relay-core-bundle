<?php

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\CoreBundle\Service\GuzzleLogger;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Jose\Component\Core\JWKSet;
use Jose\Easy\Load;
use Jose\Easy\Validate;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Helper class for validating access tokens. Either by asking the keycloak server or by locally
 * checking if the JWT is signed correctly, isn't expired etc.
 *
 * If you don't know what you are doing don't use the local validation.
 */
class KeycloakTokenValidator
{
    private $keycloak;
    private $cachePool;
    private $guzzleLogger;
    private $clientHandler;

    /* The duration the public keycloak cert is cached */
    private const CERT_CACHE_TTL_SECONDS = 3600;

    /* The leeway given for time based checks for token validation, in case the clocks of the server are out of sync */
    private const LOCAL_LEEWAY_SECONDS = 120;

    public function __construct(Keycloak $keycloak, CacheItemPoolInterface $cachePool, GuzzleLogger $guzzleLogger)
    {
        $this->keycloak = $keycloak;
        $this->cachePool = $cachePool;
        $this->guzzleLogger = $guzzleLogger;
        $this->clientHandler = null;
    }

    /**
     * Verifies that the token was created for the given audience.
     * If not then throws TokenValidationException.
     *
     * @param array  $jwt      The access token
     * @param string $audience The audience string
     *
     * @throws TokenValidationException
     */
    public static function checkAudience(array $jwt, string $audience): void
    {
        $value = $jwt['aud'] ?? [];

        if (\is_string($value)) {
            if ($value !== $audience) {
                throw new TokenValidationException('Bad audience');
            }
        } elseif (\is_array($value)) {
            if (!\in_array($audience, $value, true)) {
                throw new TokenValidationException('Bad audience');
            }
        } else {
            throw new TokenValidationException('Bad audience');
        }
    }

    /**
     * Replace the guzzle client handler for testing.
     *
     * @param object $handler
     */
    public function setClientHandler(?object $handler)
    {
        $this->clientHandler = $handler;
    }

    /**
     * Fetches the JWKs from the keycloak server and caches them.
     *
     * @throws TokenValidationException
     */
    private function fetchJWKs(): array
    {
        $provider = $this->keycloak;
        $certsUrl = sprintf('%s/protocol/openid-connect/certs', $provider->getBaseUrlWithRealm());

        $stack = HandlerStack::create($this->clientHandler);
        $stack->push($this->guzzleLogger->getClientHandler());
        $options = [
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        $client = new Client($options);

        $cacheMiddleWare = new CacheMiddleware(
            new GreedyCacheStrategy(
                new Psr6CacheStorage($this->cachePool),
                self::CERT_CACHE_TTL_SECONDS
            )
        );
        $stack->push($cacheMiddleWare);

        try {
            $response = $client->request('GET', $certsUrl);
        } catch (\Exception $e) {
            throw new TokenValidationException('Cert fetching failed: '.$e->getMessage());
        }

        try {
            $jwks = Tools::decodeJSON((string) $response->getBody(), true);
        } catch (JsonException $e) {
            throw new TokenValidationException('Cert fetching, invalid json: '.$e->getMessage());
        }

        return $jwks;
    }

    /**
     * Validates the token locally using the public JWK of the keycloak server.
     *
     * This is faster because everything can be cached, but tokens/sessions revoked on the keycloak server
     * will still be considered valid as long as they are not expired.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    public function validateLocal(string $accessToken): array
    {
        $jwks = $this->fetchJWKs();
        $issuer = $this->keycloak->getBaseUrlWithRealm();

        // Checks not needed/used here:
        // * sub(): This is the keycloak user ID by default, nothing we know beforehand
        // * jti(): Nothing we know beforehand
        // * aud(): The audience needs to be checked afterwards with checkAudience()
        try {
            $keySet = JWKSet::createFromKeyData($jwks);
            $validate = Load::jws($accessToken);
            $validate = $validate
                ->algs(['RS256', 'RS512'])
                ->keyset($keySet)
                ->exp(self::LOCAL_LEEWAY_SECONDS)
                ->iat(self::LOCAL_LEEWAY_SECONDS)
                ->nbf(self::LOCAL_LEEWAY_SECONDS)
                ->iss($issuer);
            assert($validate instanceof Validate);
            $jwtResult = $validate->run();
        } catch (\Exception $e) {
            throw new TokenValidationException('Token validation failed: '.$e->getMessage());
        }

        $jwt = $jwtResult->claims->all();

        // XXX: Keycloak will add extra data to the token retuend by introspection, mirror this behaviour here
        // to avoid breakage when switching between local/remote validation.
        // https://github.com/keycloak/keycloak/blob/8225157a1cecef30034530aa/services/src/main/java/org/keycloak/protocol/oidc/AccessTokenIntrospectionProvider.java#L59
        if (isset($jwt['preferred_username'])) {
            $jwt['username'] = $jwt['preferred_username'];
        }
        if (isset($jwt['azp'])) {
            $jwt['client_id'] = $jwt['azp'];
        }
        $jwt['active'] = true;

        return $jwt;
    }

    /**
     * Validates the token with the Keycloak introspection endpoint.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    public function validateRemoteIntrospect(string $accessToken): array
    {
        $stack = HandlerStack::create($this->clientHandler);
        $options = [
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $client = new Client($options);
        $stack->push($this->guzzleLogger->getClientHandler());

        $provider = $this->keycloak;
        $client_secret = $provider->getClientSecret();
        $client_id = $provider->getClientId();

        if (!$client_secret || !$client_id) {
            throw new TokenValidationException('Keycloak client ID or secret not set!');
        }

        try {
            // keep in mind that even if we are doing this request with a different client id the data returned will be
            // from the client id of token $accessToken (that's important for mapped attributes)
            $response = $client->request('POST', $provider->getTokenIntrospectionUrl(), [
                'auth' => [$client_id, $client_secret],
                'form_params' => [
                    'token' => $accessToken,
                ],
            ]);
        } catch (\Exception $e) {
            throw new TokenValidationException('Keycloak introspection failed');
        }

        try {
            $jwt = Tools::decodeJSON((string) $response->getBody(), true);
        } catch (JsonException $e) {
            throw new TokenValidationException('Cert fetching, invalid json: '.$e->getMessage());
        }

        if (!$jwt['active']) {
            throw new TokenValidationException('The token does not exist or is not valid anymore');
        }

        return $jwt;
    }
}
