<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Controller;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use DBP\API\CoreBundle\Exception\ApiError;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetOrganizationsByPerson extends AbstractController
{
    public const ITEMS_PER_PAGE = 250;

    protected $orgProvider;
    protected $personProvider;

    public function __construct(OrganizationProviderInterface $orgProvider, PersonProviderInterface $personProvider)
    {
        $this->orgProvider = $orgProvider;
        $this->personProvider = $personProvider;
    }

    public function __invoke(string $identifier, Request $request): PaginatorInterface
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $person = $this->personProvider->getPerson($identifier);

        // Users can only fetch this for themselves
        $user = $this->getUser();
        $isCurrentUser = $user ? $user->getUsername() === $person->getIdentifier() : false;
        if (!$isCurrentUser) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'Not allowed');
        }

        $context = $request->query->get('context', '');
        $lang = $request->query->get('lang', 'en');
        $orgs = $this->orgProvider->getOrganizationsByPerson($person, $context, $lang);

        $page = (int) $request->query->get('page', '1');
        $perPage = (int) $request->query->get('perPage', (string) self::ITEMS_PER_PAGE);

        return new ArrayFullPaginator($orgs, $page, $perPage);
    }
}
