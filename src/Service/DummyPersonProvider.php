<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\API\PersonProviderInterface;
use DBP\API\CoreBundle\Entity\Person;

class DummyPersonProvider implements PersonProviderInterface
{
    /**
     * @var string|null
     */
    private $currentIdentifier;

    public function __construct()
    {
        $this->currentIdentifier = null;
    }

    public function getPersons(array $filters): array
    {
        $person = $this->getCurrentPerson();
        if ($person !== null) {
            return [$person];
        }

        return [];
    }

    public function getPersonsByNameAndBirthday(string $givenName, string $familyName, \DateTime $birthDay): array
    {
        return [];
    }

    public function getPerson(string $id): Person
    {
        $person = new Person();
        $person->setIdentifier($id);
        $person->setGivenName('John');
        $person->setFamilyName('Doe');
        $person->setEmail('john.doe@example.com');

        return $person;
    }

    public function getCurrentPerson(): ?Person
    {
        if ($this->currentIdentifier === null) {
            return null;
        }

        return $this->getPerson($this->currentIdentifier);
    }

    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        return new Person();
    }

    public function setCurrentIdentifier(string $identifier): void
    {
        $this->currentIdentifier = $identifier;
    }

    public function getRolesForCurrentPerson(): array
    {
        if ($this->currentIdentifier === null) {
            return [];
        }

        return $this->getCurrentPerson()->getRoles();
    }

    public function setRolesForCurrentPerson(array $roles): void
    {
        if ($this->currentIdentifier === null) {
            return;
        }
        $this->getCurrentPerson()->setRoles($roles);
    }
}
