<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi;

use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestResourceController extends AbstractController
{
    private UserSessionInterface $userSession;

    public function __construct(UserSessionInterface $userSession)
    {
        $this->userSession = $userSession;
    }

    public function __invoke(string $identifier, Request $request): ?TestResource
    {
        $test = $request->get('test');
        $parameter = $request->get('param');
        $resource = new TestResource();
        $resource->setIdentifier($identifier);

        if ($test === 'GetCurrentUser') {
            $resource->setContent(json_encode([
                'userIdentifier' => $this->userSession->getUserIdentifier(),
                'isAuthenticated' => $this->userSession->isAuthenticated(),
                'userRoles' => $this->userSession->getUserRoles(),
            ]));
        } elseif ($test === 'ApiError') {
            throw ApiError::withDetails(Response::HTTP_I_AM_A_TEAPOT, 'some message', 'some-error-id', ['detail1' => '1', 'detail2' => '2']);
        } elseif ($test === 'UnhandledError') {
            throw new \RuntimeException('oh no');
        } elseif ($test === 'denyAccessUnlessGranted') {
            if ($parameter === null) {
                throw new \RuntimeException('missing param');
            }
            $this->denyAccessUnlessGranted($parameter);
        } else {
            throw new \RuntimeException('unknown tests');
        }

        return $resource;
    }
}
