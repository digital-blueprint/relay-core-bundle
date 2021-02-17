<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DBP\API\AlmaBundle\Controller\GetLibraryBookLoansByOrganization;
use DBP\API\AlmaBundle\Controller\GetLibraryBookOffersByOrganization;
use DBP\API\AlmaBundle\Controller\GetLibraryBookOrdersByOrganization;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('IS_AUTHENTICATED_FULLY')"},
 *     collectionOperations={
 *       "get"={"security"="is_granted('IS_AUTHENTICATED_FULLY')"},
 *     },
 *     itemOperations={
 *       "get"={
 *         "security"="is_granted('IS_AUTHENTICATED_FULLY')",
 *         "openapi_context"={"parameters"={
 *           {"name"="id", "in"="path", "description"="orgUnitID of organization", "required"=true, "type"="string", "example"="1190-F2050"},
 *           {"name"="lang", "in"="query", "description"="Language of result", "type"="string", "enum"={"de", "en"}, "example"="de"}}}
 *       },
 *       "get_library_book_offers"={
 *         "security"="is_granted('IS_AUTHENTICATED_FULLY')",
 *         "method"="GET",
 *         "path"="/organizations/{id}/library-book-offers",
 *         "controller"=GetLibraryBookOffersByOrganization::class,
 *         "normalization_context"={"jsonld_embed_context"=true, "groups"={"LibraryBook:output", "LibraryBookOffer:output"}},
 *         "openapi_context"=
 *           {"summary"="Get the library book offers of an organization.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of organization", "required"=true, "type"="string", "example"="1190-F2050"}}},
 *       },
 *       "get_library_book_loans"={
 *         "security"="is_granted('IS_AUTHENTICATED_FULLY')",
 *         "method"="GET",
 *         "path"="/organizations/{id}/library-book-loans",
 *         "controller"=GetLibraryBookLoansByOrganization::class,
 *         "normalization_context"={"jsonld_embed_context"=true, "groups"={"LibraryBookLoan:output", "Person:output", "LibraryBookOffer:output", "LibraryBook:output"}},
 *         "openapi_context"=
 *           {"summary"="Get the library book loans of an organization.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of organization", "required"=true, "type"="string", "example"="1190-F2050"}}},
 *       },
 *       "get_library_book_orders"={
 *         "security"="is_granted('IS_AUTHENTICATED_FULLY')",
 *         "method"="GET",
 *         "path"="/organizations/{id}/library-book-orders",
 *         "controller"=GetLibraryBookOrdersByOrganization::class,
 *         "normalization_context"={"groups"={"LibraryBookOrder:output", "LibraryBookOrderItem:output", "ParcelDelivery:output", "DeliveryStatus:output", "DeliveryEvent:output", "LibraryBook:output", "EventStatusType:output"}},
 *         "openapi_context"=
 *           {"summary"="Get the library book orders of an organization.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of organization", "required"=true, "type"="string", "example"="1190-F2050"}}},
 *       }
 *     },
 *     iri="http://schema.org/Organization",
 *     description="An organization",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"Organization:output"}}
 * )
 */
class Organization
{
    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/name")
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/url")
     *
     * @var string
     */
    private $url;

    /**
     * @Groups({"Organization:output"})
     * @ApiProperty(iri="https://schema.org/alternateName")
     *
     * @var string
     */
    private $alternateName;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setAlternateName(string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
