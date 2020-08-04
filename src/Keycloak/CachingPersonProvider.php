<?php

namespace DBP\API\CoreBundle\Keycloak;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches the requested Person using the current JWT as a key
 */
class CachingPersonProvider implements PersonProviderInterface
{
    private $provider;
    private $cache;
    private $jwt;

    public function __construct(PersonProviderInterface $provider, CacheItemPoolInterface $cache, array $jwt)
    {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->jwt = $jwt;
    }

    public function getPersons(array $filters): array
    {
        return $this->provider->getPersons($filters);
    }

    public function getPerson(string $id, bool $full): Person
    {
        assert($this->jwt['active']);

        $cacheKey = hash('sha256', $id . '.' . $full . '.' . json_encode($this->jwt));

        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            return $item->get();
        } else {
            $person = $this->provider->getPerson($id, $full);
            $item->set($person);

            // Just use the time the token is valid plus a bit more, this makes sure the cache is valid
            // until the token expires but doesn't stay around forever.
            $expiresAfter = max($this->jwt['exp'] - $this->jwt['iat'], 0) * 2;
            $item->expiresAfter($expiresAfter);

            $this->cache->save($item);
            return $person;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCurrentPerson(): Person
    {
        return $this->provider->getCurrentPerson();
    }

    /**
     * @inheritDoc
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        return $this->provider->getPersonForExternalService($service, $serviceID);
    }
}