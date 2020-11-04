<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;

interface PersonProviderInterface
{
    /**
     * @throws ItemNotLoadedException
     */
    public function getPersons(array $filters): array;

    /**
     * @return Person[]
     *
     * @throws ItemNotLoadedException
     */
    public function getPersonsByNameAndBirthday(string $givenName, string $familyName, \DateTime $birthDay): array;

    /**
     * @throws ItemNotLoadedException
     */
    public function getPerson(string $id): Person;

    /**
     * @throws ItemNotLoadedException
     */
    public function getCurrentPerson(): Person;

    /**
     * @throws ItemNotLoadedException
     * @throws ItemNotFoundException
     */
    public function getPersonForExternalService(string $service, string $serviceID): Person;
}
