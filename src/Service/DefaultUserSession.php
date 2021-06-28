<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\API\UserSessionInterface;

class DefaultUserSession implements UserSessionInterface
{
    /**
     * @var ?array
     */
    private $jwt;

    public function __construct()
    {
        $this->jwt = null;
    }

    public function getUserIdentifier(): ?string
    {
        assert($this->jwt !== null);

        if (self::isServiceAccountToken($this->jwt)) {
            return null;
        }

        return $this->jwt['username'] ?? null;
    }

    public function getUserRoles(): array
    {
        assert($this->jwt !== null);

        $scopes = [];
        if ($this->jwt['scope'] ?? '' !== '') {
            $scopes = explode(' ', $this->jwt['scope']);
        }

        $roles = [];
        foreach ($scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.mb_strtoupper($scope);
        }

        return $roles;
    }

    /**
     * Given a token returns if the token was generated through a client credential flow.
     */
    public static function isServiceAccountToken(array $jwt): bool
    {
        if (!array_key_exists('scope', $jwt)) {
            throw new \RuntimeException('Token missing scope key');
        }
        $scope = $jwt['scope'];
        // XXX: This is the main difference I found compared to other flows, but that's a Keycloak
        // implementation detail I guess.
        $has_openid_scope = in_array('openid', explode(' ', $scope), true);

        return !$has_openid_scope;
    }

    public function setSessionToken(?array $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getSessionLoggingId(): string
    {
        $unknown = 'unknown';

        if ($this->jwt === null) {
            return $unknown;
        }
        assert($this->jwt !== null);

        // We want to know where the request is coming from and which requests likely belong together for debugging
        // purposes while still preserving the privacy of the user.
        // The session ID gets logged in the Keycloak event log under 'code_id' and stays the same during a login
        // session. When the event in keycloak expires it's no longer possible to map the ID to a user.
        // The keycloak client ID is in azp, so add that too, and hash it with the user ID so we get different
        // user ids for different clients for the same session.

        $jwt = $this->jwt;
        $client = $jwt['azp'] ?? $unknown;
        if (!isset($jwt['session_state'])) {
            $user = $unknown;
        } else {
            // TODO: If we'd have an app secret we could hash that in too
            $user = substr(hash('sha256', $client.$jwt['session_state']), 0, 6);
        }

        return $client.'-'.$user;
    }

    public function getSessionCacheKey(): string
    {
        assert($this->jwt !== null);

        return hash('sha256', $this->getUserIdentifier().'.'.json_encode($this->jwt));
    }

    public function getSessionTTL(): int
    {
        assert($this->jwt !== null);

        return max($this->jwt['exp'] - $this->jwt['iat'], 0);
    }
}
