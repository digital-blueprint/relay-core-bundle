<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Helpers\GuzzleTools;
use DBP\API\CoreBundle\Helpers\Tools;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class KeycloakRemoteTokenValidator extends KeycloakTokenValidatorBase
{
    private $keycloak;
    private $clientHandler;

    public function __construct(Keycloak $keycloak)
    {
        $this->keycloak = $keycloak;
        $this->clientHandler = null;
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
     * Validates the token with the Keycloak introspection endpoint.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    public function validate(string $accessToken): array
    {
        $stack = HandlerStack::create($this->clientHandler);
        $options = [
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $client = new Client($options);
        if ($this->logger !== null) {
            $stack->push(GuzzleTools::createLoggerMiddleware($this->logger));
        }

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
        } catch (\JsonException $e) {
            throw new TokenValidationException('Cert fetching, invalid json: '.$e->getMessage());
        }

        if (!$jwt['active']) {
            throw new TokenValidationException('The token does not exist or is not valid anymore');
        }

        return $jwt;
    }
}
