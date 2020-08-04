<?php


namespace DBP\API\CoreBundle\Service;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;

interface PersonProviderInterface
{
    /**
     * @param array $filters
     * @return array
     * @throws ItemNotLoadedException
     */
    public function getPersons(array $filters): array;

    /**
     * @param string $id
     * @param bool $full
     * @return Person
     * @throws ItemNotLoadedException
     */
    public function getPerson(string $id, bool $full): Person;

    /**
     * @return Person
     * @throws ItemNotLoadedException
     */
    public function getCurrentPerson(): Person;

    /**
     * @return Person
     * @throws ItemNotLoadedException
     * @throws ItemNotFoundException
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person;
}