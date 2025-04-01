<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\TestApi\Rest;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\CustomControllerTrait;
use Dbp\Relay\CoreBundle\Tests\TestApi\Entity\TestResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

class TestResourceCollectionController extends AbstractController
{
    use CustomControllerTrait;

    /**
     * @return TestResource[]
     *
     * @throws ApiError
     */
    public function __invoke(Request $request): array
    {
        $this->requireAuthentication();

        $test = $request->get('test');

        if ($test === 'CustomControllerTesterTest_CollectionOperation') {
            $resource = new TestResource();
            $resource->setIdentifier((string) Uuid::v7());
            $resource->setContent($request->query->get('content'));
            $resource->setIsPublic($request->request->get('isPublic', false));
            $resource->setFile($request->files->get('file'));
            $returnValue = [$resource];
        } else {
            throw new \RuntimeException('unknown test: '.$test);
        }

        return $returnValue;
    }
}
