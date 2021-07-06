<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\API\UserSessionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakBearerUserProvider implements KeycloakBearerUserProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $config;
    private $certCachePool;
    private $personCachePool;
    private $userSession;

    public function __construct(UserSessionInterface $userSession)
    {
        $this->userSession = $userSession;
        $this->config = [];
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setCertCache(?CacheItemPoolInterface $cachePool)
    {
        $this->certCachePool = $cachePool;
    }

    public function loadUserByToken(string $accessToken): UserInterface
    {
        $config = $this->config;
        $keycloak = new Keycloak(
            $config['server_url'], $config['realm'],
            $config['client_id'], $config['client_secret']);

        if ($config['local_validation']) {
            $validator = new KeycloakLocalTokenValidator($keycloak, $this->certCachePool);
        } else {
            $validator = new KeycloakRemoteTokenValidator($keycloak);
        }
        $validator->setLogger($this->logger);

        $jwt = $validator->validate($accessToken);

        if (($config['audience'] ?? '') !== '') {
            $validator::checkAudience($jwt, $config['audience']);
        }

        return $this->loadUserByValidatedToken($jwt);
    }

    public function loadUserByValidatedToken(array $jwt): UserInterface
    {
        $session = $this->userSession;
        $session->setSessionToken($jwt);
        $identifier = $session->getUserIdentifier();
        $userRoles = $session->getUserRoles();

        return new KeycloakBearerUser(
            $identifier,
            $userRoles
        );
    }
}
