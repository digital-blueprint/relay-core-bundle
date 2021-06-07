<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakBearerUserProvider implements UserProviderInterface
{
    private $personProvider;
    private $logger;
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

    public function __construct(ContainerInterface $container, PersonProviderInterface $personProvider, LoggerInterface $logger)
    {
        $this->personProvider = $personProvider;
        $this->logger = $logger;
        $this->container = $container;
        $this->config = $container->getParameter('dbp_api.core.keycloak_config');
    }

    public function createLoggingID(array $jwt): string
    {
        // We want to know where the request is coming from and which requests likely belong together for debugging
        // purposes while still preserving the privacy of the user.
        // The session ID gets logged in the Keycloak event log under 'code_id' and stays the same during a login
        // session. When the event in keycloak expires it's no longer possible to map the ID to a user.
        // The keycloak client ID is in azp, so add that too, and hash it with the user ID so we get different
        // user ids for different clients for the same session.

        $client = $jwt['azp'] ?? 'unknown';
        if (!isset($jwt['session_state'])) {
            $user = 'unknown';
        } else {
            // TODO: If we'd have an app secret we could hash that in too
            $user = substr(hash('sha256', $client.$jwt['session_state']), 0, 6);
        }

        return $client.'-'.$user;
    }

    public function loadUserByUsername($username): UserInterface
    {
        $accessToken = $username;
        $guzzleCache = $this->container->get('dbp_api.cache.core.keycloak_cert');
        assert($guzzleCache instanceof CacheItemPoolInterface);

        $config = $this->config;
        $keycloak = new Keycloak(
            $config['server_url'], $config['realm'],
            $config['client_id'], $config['client_secret']);

        if ($config['local_validation']) {
            $validator = new KeycloakLocalTokenValidator($keycloak, $guzzleCache, $this->logger);
        } else {
            $validator = new KeycloakRemoteTokenValidator($keycloak, $this->logger);
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

        $user = new KeycloakBearerUser(
            $username,
            $accessToken,
            $cachingPersonProvider,
            $scopes
        );
        $user->setLoggingID($this->createLoggingID($jwt));

        return $user;
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
