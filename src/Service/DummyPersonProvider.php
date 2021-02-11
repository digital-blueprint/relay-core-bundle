<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\Security\Core\Security;

class DummyPersonProvider implements PersonProviderInterface
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getPersons(array $filters): array
    {
        return [$this->getCurrentPerson()];
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

    public function getCurrentPerson(): Person
    {
        $user = $this->security->getUser();

        return $this->getPerson($user->getUsername());
    }

    public function getPersonForExternalService(string $service, string $serviceID): Person
    {
        return new Person();
    }

    public function getRolesForScopes(array $scopes): array
    {
        return [];
    }
}
