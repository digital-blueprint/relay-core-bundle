<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Swagger;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use Dbp\Relay\CoreBundle\Swagger\OpenApiDecorator;
use PHPUnit\Framework\TestCase;

class OpenApiDecoratorTest extends TestCase
{
    public function testAddsAcceptLanguageParameter(): void
    {
        $mockFactory = $this->createMock(OpenApiFactoryInterface::class);
        $paths = new Paths();
        $paths->addPath('/test', new PathItem(get: new Operation(operationId: 'test')));

        $openApi = new OpenApi(
            info: new Info(title: 'Test', version: '1.0'),
            servers: [],
            paths: $paths
        );

        $mockFactory->method('__invoke')->willReturn($openApi);
        $decorator = new OpenApiDecorator($mockFactory);

        $result = $decorator();

        $parameters = $result->getPaths()->getPath('/test')->getParameters();
        $this->assertCount(1, $parameters);
        $langParam = $parameters[0];

        $this->assertEquals('Accept-Language', $langParam->getName());
        $this->assertEquals('header', $langParam->getIn());
        $this->assertEquals(['de', 'en'], $langParam->getSchema()['enum']);
    }
}
