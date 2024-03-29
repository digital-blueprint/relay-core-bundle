<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionTraitTest extends TestCase
{
    use ExtensionTrait;

    public function testAll()
    {
        $builder = new ContainerBuilder();
        $params = $builder->getParameterBag();
        $this->addQueueMessageClass($builder, 'foobar');
        $this->assertTrue($params->has('dbp_api.messenger_routing'));
        $this->addResourceClassDirectory($builder, '.');
        $this->assertTrue($params->has('api_platform.resource_class_directories'));
        $this->addPathToHide($builder, '/');
        $this->assertTrue($params->has('dbp_api.paths_to_hide'));
        $this->addPathToHide($builder, '/', 'POST');
        $this->addRouteResource($builder, '.', null);
        $this->assertTrue($params->has('dbp_api.route_resources'));
        $this->addExposeHeader($builder, 'foobar');
        $this->assertTrue($params->has('dbp_api.expose_headers'));
        $this->addAllowHeader($builder, 'foobar');
        $this->assertTrue($params->has('dbp_api.allow_headers'));
        $this->registerEntityManager($builder, 'some_entity_manager');
        $this->assertTrue($params->has('dbp_api.entity_managers'));
        $this->registerLoggingChannel($builder, 'mychannel', false);
        $this->assertTrue($params->has('dbp_api.logging_channels'));
    }

    public function testCalledTooLate()
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('dbp_api._prepend_done', true);
        $this->expectException(\RuntimeException::class);
        $this->addQueueMessageClass($builder, 'foobar');
    }
}
