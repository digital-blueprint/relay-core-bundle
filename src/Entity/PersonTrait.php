<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

trait PersonTrait
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/givenName")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $givenName;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/familyName")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $familyName;

    /**
     * @ApiProperty(iri="http://schema.org/honorificSuffix")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $honorificSuffix;

    /**
     * @ApiProperty(iri="http://schema.org/telephone")
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $telephone;

    /**
     * @var string
     * @Groups({"Person:output"})
     *
     * @var string
     */
    private $phoneExtension;

    /**
     * @ApiProperty(iri="http://schema.org/email")
     * @Groups({"Person:current-user", "Person:extended-access"})
     *
     * @var string
     */
    private $email;

    /**
     * @var string|null
     * @ApiProperty(iri="http://schema.org/image", required=false)
     * @Groups({"Person:current-user"})
     */
    private $image;

    /**
     * @var array
     * @Groups({"Person:current-user"})
     */
    private $roles;

    /**
     * @var string
     * @ApiProperty(iri="http://schema.org/birthDate")
     * @Groups({"Person:current-user"})
     */
    private $birthDate;

    /**
     * @var array
     */
    private $extraData;

    public function __construct()
    {
        $this->extraData = [];
        $this->roles = [];
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getHonorificSuffix(): ?string
    {
        return $this->honorificSuffix;
    }

    public function setHonorificSuffix(?string $honorificSuffix): self
    {
        $this->honorificSuffix = $honorificSuffix;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getPhoneExtension(): ?string
    {
        return $this->phoneExtension;
    }

    public function setPhoneExtension(?string $phoneExtension): self
    {
        $this->phoneExtension = $phoneExtension;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Allows attaching extra information to a Person object with
     * some random key. You can get the value back via getExtraData().
     *
     * @param ?mixed $value
     */
    public function setExtraData(string $key, $value): void
    {
        $this->extraData[$key] = $value;
    }

    /**
     * @return ?mixed
     */
    public function getExtraData(string $key)
    {
        return $this->extraData[$key] ?? null;
    }

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"Person:output"})
     */
    public function getName(): ?string
    {
        return $this->givenName.' '.$this->familyName;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return Person
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }
}
