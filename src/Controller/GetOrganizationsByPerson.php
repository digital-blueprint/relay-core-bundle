<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Controller;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ApiError;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetOrganizationsByPerson extends AbstractController
{
    protected $api;
    protected $security;

    public function __construct(OrganizationProviderInterface $api)
    {
        $this->api = $api;
    }

    public function __invoke(Person $data, Request $request): array
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Users can only fetch this for themselves
        $user = $this->getUser();
        $isCurrentUser = $user ? $user->getUsername() === $data->getIdentifier() : false;
        if (!$isCurrentUser) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'Not allowed');
        }

        $context = $request->query->get('context');
        if ($context === null) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, 'missing type parameter');
        }
        $lang = $request->query->get('lang', 'en');

        return $this->api->getOrganizationsByPerson($data, $context, $lang);
    }
}
