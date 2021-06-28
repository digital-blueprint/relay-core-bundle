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
     * This gets called after the user is authenticated.
     *
     * @param string $identifier The identifier of the person matching the current user
     */
    public function setCurrentIdentifier(string $identifier): void;

    /**
     * Returns the Person matching the current user. Or null if there is no associated person
     * like when the client is another server.
     */
    public function getCurrentPerson(): ?Person;

    /**
     * Should return a list of Symonfy roles for the current Person. These will be added
     * to the active Symfony user object. In case there is none returns an empty array.
     *
     * @return string[]
     */
    public function getRolesForCurrentPerson(): array;

    /**
     * Should set the given set of Symfony roles to the Person matching the current user. Assuming there is one.
     *
     * @param string[] $roles
     */
    public function setRolesForCurrentPerson(array $roles): void;
}
