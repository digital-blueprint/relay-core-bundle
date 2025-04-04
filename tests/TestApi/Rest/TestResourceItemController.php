<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\CustomControllerTrait;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Dbp\Relay\CoreBundle\Tests\TestApi\Service\TestResourceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TestResourceItemController extends AbstractController
{
    use CustomControllerTrait;

    public function __construct(private readonly TestResourceService $testResourceService)
    {
    }

    /**
     * @throws ApiError
     */
    public function __invoke(Request $request, string $identifier): ?TestResource
    {
        $test = $request->get('test');
        $parameter = $request->get('param');

        if ($test !== 'GetCurrentUser') {
            $this->requireAuthentication();
        }

        $testResource = new TestResource();
        $testResource->setIdentifier($identifier);

        if ($test === 'GetCurrentUser') {
            $testResource->setContent(json_encode([
                'userIdentifier' => $this->userSession->isAuthenticated() ? $this->userSession->getUserIdentifier() : null,
                'isAuthenticated' => $this->userSession->isAuthenticated(),
                'userRoles' => $this->userSession->isAuthenticated() ? $this->userSession->getUserRoles() : [],
            ]));
        } elseif ($test === 'ApiErrorDetails') {
            throw ApiError::withDetails(Response::HTTP_I_AM_A_TEAPOT, 'some message', 'some-error-id', ['detail1' => '1', 'detail2' => ['2', '3']]);
        } elseif ($test === 'ApiErrorDetailsDefault') {
            throw ApiError::withDetails(Response::HTTP_I_AM_A_TEAPOT);
        } elseif ($test === 'ApiError') {
            throw new ApiError(Response::HTTP_I_AM_A_TEAPOT);
        } elseif ($test === 'ApiError500') {
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, "it wasn't me");
        } elseif ($test === 'HttpException418') {
            throw new HttpException(418, 'not again');
        } elseif ($test === 'HttpException500') {
            throw new HttpException(500, 'totally unexpected');
        } elseif ($test === 'UnhandledError') {
            throw new \RuntimeException('oh no');
        } elseif ($test === 'denyAccessUnlessGranted') {
            if ($parameter === null) {
                throw new \RuntimeException('missing param');
            }
            $this->denyAccessUnlessGranted($parameter);
        } elseif ($test === 'GetResourceAuthenticatedOnly') {
        } elseif ($test === 'CustomControllerTesterTest_ItemOperation') {
            $testResource->setContent($request->query->get('content'));
            $testResource->setIsPublic($request->request->get('isPublic', false));
            $testResource->setFile($request->files->get('file'));
        } else {
            throw new \RuntimeException('unknown test: '.$test);
        }

        return $testResource;
    }
}
