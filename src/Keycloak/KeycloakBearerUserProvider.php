<?php

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Service\GuzzleLogger;
use App\Service\PersonProviderInterface;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakBearerUserProvider implements UserProviderInterface
{
    private $personProvider;
    private $guzzleLogger;
    private $container;

    /**
     * Given a token returns if the token was generated through a client credential flow.
     *
     * @param array $jwt
     * @return bool
     */
    static function isServiceAccountToken(array $jwt) : bool {
        if (!array_key_exists("scope", $jwt))
            throw new \RuntimeException('Token missing scope key');
        $scope = $jwt['scope'];
        // XXX: This is the main difference I found compared to other flows, but that's a Keycloak
        // implementation detail I guess.
        $has_openid_scope = in_array("openid", explode(" ", $scope), true);
        return !$has_openid_scope;
    }

    public function __construct(ContainerInterface $container, PersonProviderInterface $personProvider, GuzzleLogger $guzzleLogger)
    {
        $this->personProvider = $personProvider;
        $this->guzzleLogger = $guzzleLogger;
        $this->container = $container;
    }

    private function useLocalValidation() {
        return $_ENV['KEYCLOAK_LOCAL_VALIDATION'] === 'true';
    }

    public function loadUserByUsername($accessToken): UserInterface
    {
        $guzzleCache = $this->container->get('cache.dbp.guzzle');
        assert($guzzleCache instanceof CacheItemPoolInterface);

        $keycloak = new Keycloak(
            $_ENV['KEYCLOAK_SERVER_URL'], $_ENV['KEYCLOAK_REALM'],
            $_ENV['KEYCLOAK_CLIENT_ID'], $_ENV['KEYCLOAK_CLIENT_SECRET']);

        $validator = new KeycloakTokenValidator($keycloak, $guzzleCache, $this->guzzleLogger);
        if ($this->useLocalValidation())
            $jwt = $validator->validateLocal($accessToken);
        else
            $jwt = $validator->validateRemoteIntrospect($accessToken);

        if ($_ENV['KEYCLOAK_AUDIENCE'] ?? '' !== '') {
            $validator::checkAudience($jwt, $_ENV['KEYCLOAK_AUDIENCE']);
        }

        $cache = $this->container->get('cache.dbp.auth_person');
        assert($cache instanceof CacheItemPoolInterface);
        $cachingPersonProvider = new CachingPersonProvider($this->personProvider, $cache, $jwt);

        $username = $jwt['username'];
        $scopes = explode(' ', $jwt['scope']);

        return new KeycloakBearerUser(
            $username,
            $accessToken,
            $cachingPersonProvider,
            self::isServiceAccountToken($jwt),
            $scopes
        );
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return KeycloakBearerUser::class === $class;
    }
}
