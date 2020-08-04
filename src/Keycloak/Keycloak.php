<?php

namespace DBP\API\CoreBundle\Keycloak;

class Keycloak
{
    /**
     * @var string
     */
    private $authServerUrl = null;

    /**
     * @var string
     */
    private $realm = null;

    /**
     * @var string
     */
    private $clientId = null;

    /**
     * @var string
     */
    private $clientSecret = null;

    public function __construct(string $serverUrl, string $realm, string $cliendId = null, string $clientSecret = null)
    {
        $this->authServerUrl = $serverUrl;
        $this->realm = $realm;
        $this->clientId = $cliendId;
        $this->clientSecret = $clientSecret;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getBaseUrlWithRealm()
    {
        return sprintf('%s/realms/%s', $this->authServerUrl, $this->realm);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return sprintf('%s/protocol/openid-connect/auth', $this->getBaseUrlWithRealm());
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return sprintf('%s/protocol/openid-connect/token', $this->getBaseUrlWithRealm());
    }

    public function getTokenIntrospectionUrl(): string
    {
        return sprintf('%s/protocol/openid-connect/token/introspect', $this->getBaseUrlWithRealm());
    }
}
