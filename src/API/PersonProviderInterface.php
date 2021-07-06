<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\API;

use DBP\API\CoreBundle\Entity\Person;

interface PersonProviderInterface
{
    /**
     * @return Person[]
     */
    public function getPersons(array $filters): array;

    /**
     * @return Person[]
     */
    public function getPersonsByNameAndBirthday(string $givenName, string $familyName, \DateTime $birthDay): array;

    public function getPerson(string $id): Person;

    public function getPersonForExternalService(string $service, string $serviceID): Person;

    /**
     * Returns the Person matching the current user. Or null if there is no associated person
     * like when the client is another server.
     */
    public function getCurrentPerson(): ?Person;
}
