<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\TestUtils;

use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\CoreBundle\API\PersonProviderInterface;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;

class DummyPersonProvider implements PersonProviderInterface
{
    /* @var Person */
    private $person;

    public function __construct($person)
    {
        $this->person = $person;
    }

    public function getPersons(array $filters): array
    {
        return [$this->person];
    }

    public function getPerson(string $id): Person
    {
        if ($id !== $this->person->getIdentifier()) {
            throw new ItemNotLoadedException();
        }

        return $this->person;
    }

    public function getCurrentPerson(): Person
    {
        return $this->person;
    }

    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        throw new ItemNotFoundException();
    }

    public function getPersonsByNameAndBirthDate(string $givenName, string $familyName, string $birthDate): array
    {
        return [];
    }

    public function setCurrentIdentifier(string $identifier): void
    {
        $this->person->setIdentifier($identifier);
    }

    public function getRolesForCurrentPerson(): array
    {
        return $this->person->getRoles();
    }

    public function setRolesForCurrentPerson(array $roles): void
    {
        $this->person->setRoles($roles);
    }
}
