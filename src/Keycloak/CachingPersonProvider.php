<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Keycloak;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches the requested Person using the current JWT as a key.
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

    /**
     * @param string $firstName
     * @param string $lastName
     * @param \DateTime $birthDay
     * @return Person[]
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getPersonsByNameAndBirthday(string $givenName, string $familyName, \DateTime $birthDay): array
    {
        return $this->provider->getPersonsByNameAndBirthday($firstName, $lastName, $birthDay);
    }

    public function getPerson(string $id, bool $full): Person
    {
        assert($this->jwt['active']);

        $cacheKey = hash('sha256', $id.'.'.$full.'.'.json_encode($this->jwt));

        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $person = $item->get();
            if ($person === null) {
                throw new ItemNotFoundException();
            }

            return $person;
        } else {
            try {
                $person = $this->provider->getPerson($id, $full);
            } catch (ItemNotFoundException $e) {
                $person = null;
            }
            $item->set($person);

            // Just use the time the token is valid plus a bit more, this makes sure the cache is valid
            // until the token expires but doesn't stay around forever.
            $expiresAfter = max($this->jwt['exp'] - $this->jwt['iat'], 0) * 2;
            $item->expiresAfter($expiresAfter);

            $this->cache->save($item);

            if ($person === null) {
                throw new ItemNotFoundException();
            }

            return $person;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPerson(): Person
    {
        return $this->provider->getCurrentPerson();
    }

    /**
     * {@inheritdoc}
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        return $this->provider->getPersonForExternalService($service, $serviceID);
    }
}
