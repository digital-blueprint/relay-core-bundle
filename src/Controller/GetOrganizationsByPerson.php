<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Controller;

use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Exception\ApiError;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class GetOrganizationsByPerson
{
    protected $api;
    protected $security;

    public function __construct(Security $security, OrganizationProviderInterface $api)
    {
        $this->api = $api;
        $this->security = $security;
    }

    public function __invoke(Person $data, Request $request): array
    {
        // Users can only fetch this for themselves
        $user = $this->security->getUser();
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
