<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Service\GuzzleLogger;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakBearerUserProvider implements UserProviderInterface
{
    private $personProvider;
    private $guzzleLogger;
    private $container;
    private $config;

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

    public function __construct(ContainerInterface $container, PersonProviderInterface $personProvider, GuzzleLogger $guzzleLogger)
    {
        $this->personProvider = $personProvider;
        $this->guzzleLogger = $guzzleLogger;
        $this->container = $container;
        $this->config = $container->getParameter('dbp_api.core.keycloak_config');
    }

    public function loadUserByUsername($accessToken): UserInterface
    {
        $guzzleCache = $this->container->get('dbp_api.cache.core.keycloak_cert');
        assert($guzzleCache instanceof CacheItemPoolInterface);

        $config = $this->config;
        $keycloak = new Keycloak(
            $config['server_url'], $config['realm'],
            $config['client_id'], $config['client_secret']);

        if ($config['local_validation']) {
            $validator = new KeycloakLocalTokenValidator($keycloak, $guzzleCache, $this->guzzleLogger);
        } else {
            $validator = new KeycloakRemoteTokenValidator($keycloak, $this->guzzleLogger);
        }

        $jwt = $validator->validate($accessToken);

        if (($config['audience'] ?? '') !== '') {
            $validator::checkAudience($jwt, $config['audience']);
        }

        $cache = $this->container->get('dbp_api.cache.core.auth_person');
        assert($cache instanceof CacheItemPoolInterface);
        $cachingPersonProvider = new CachingPersonProvider($this->personProvider, $cache, $jwt);

        if (self::isServiceAccountToken($jwt)) {
            $username = null;
        } else {
            $username = $jwt['username'] ?? null;
        }
        $scopes = explode(' ', $jwt['scope']);

        return new KeycloakBearerUser(
            $username,
            $accessToken,
            $cachingPersonProvider,
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
