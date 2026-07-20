<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests;

use Dbp\Relay\CoreBundle\DB\MigrateCommand;
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
        $this->assertSame(['some_entity_manager' => []], $builder->getParameter('dbp_api.entity_managers'));
        $this->registerLoggingChannel($builder, 'mychannel', false);
        $this->assertTrue($params->has('dbp_api.logging_channels'));
    }

    public function testExposeHeaderGlobal()
    {
        $builder = new ContainerBuilder();
        $this->addExposeHeader($builder, 'X-My-Header');
        $entries = $builder->getParameter('dbp_api.expose_headers');
        $this->assertCount(1, $entries);
        $this->assertSame(['X-My-Header', '/'], $entries[0]);
    }

    public function testExposeHeaderWithPrefix()
    {
        $builder = new ContainerBuilder();
        $this->addExposeHeader($builder, 'X-My-Header', '/my-bundle');
        $entries = $builder->getParameter('dbp_api.expose_headers');
        $this->assertCount(1, $entries);
        $this->assertSame(['X-My-Header', '/my-bundle'], $entries[0]);
    }

    public function testAllowHeaderGlobal()
    {
        $builder = new ContainerBuilder();
        $this->addAllowHeader($builder, 'X-My-Header');
        $entries = $builder->getParameter('dbp_api.allow_headers');
        $this->assertCount(1, $entries);
        $this->assertSame(['X-My-Header', '/'], $entries[0]);
    }

    public function testAllowHeaderWithPrefix()
    {
        $builder = new ContainerBuilder();
        $this->addAllowHeader($builder, 'X-My-Header', '/my-bundle');
        $entries = $builder->getParameter('dbp_api.allow_headers');
        $this->assertCount(1, $entries);
        $this->assertSame(['X-My-Header', '/my-bundle'], $entries[0]);
    }

    public function testMultipleCallsSamePrefix()
    {
        $builder = new ContainerBuilder();
        $this->addExposeHeader($builder, 'X-First', '/my-bundle');
        $this->addExposeHeader($builder, 'X-Second', '/my-bundle');
        $this->addAllowHeader($builder, 'X-Auth', '/my-bundle');
        $entries = $builder->getParameter('dbp_api.expose_headers');
        $this->assertCount(2, $entries);
        $this->assertSame(['X-First', '/my-bundle'], $entries[0]);
        $this->assertSame(['X-Second', '/my-bundle'], $entries[1]);
        $allowEntries = $builder->getParameter('dbp_api.allow_headers');
        $this->assertCount(1, $allowEntries);
        $this->assertSame(['X-Auth', '/my-bundle'], $allowEntries[0]);
    }

    public function testMixedGlobalAndPrefixHeaders()
    {
        $builder = new ContainerBuilder();
        $this->addExposeHeader($builder, 'X-Global');
        $this->addExposeHeader($builder, 'X-Scoped', '/api/my-bundle');
        $entries = $builder->getParameter('dbp_api.expose_headers');
        $this->assertCount(2, $entries);
        $this->assertSame(['X-Global', '/'], $entries[0]);
        $this->assertSame(['X-Scoped', '/api/my-bundle'], $entries[1]);
    }

    public function testRegisterEntityManagerWithDependencies()
    {
        $builder = new ContainerBuilder();
        $this->registerEntityManager($builder, 'em_a');
        $this->registerEntityManager($builder, 'em_b', ['em_a']);
        // Re-registering merges dependencies without duplicates
        $this->registerEntityManager($builder, 'em_b', ['em_a', 'em_c']);
        $this->assertSame([
            'em_a' => [],
            'em_b' => ['em_a', 'em_c'],
        ], $builder->getParameter('dbp_api.entity_managers'));
    }

    public function testSortEntityManagers()
    {
        $sorted = MigrateCommand::sortEntityManagers([
            'em_c' => ['em_b'],
            'em_b' => ['em_a'],
            'em_a' => [],
        ]);
        $this->assertSame(['em_a', 'em_b', 'em_c'], $sorted);
    }

    public function testSortEntityManagersUnknownDependencyThrows()
    {
        $this->expectException(\RuntimeException::class);
        MigrateCommand::sortEntityManagers([
            'em_a' => ['em_missing'],
        ]);
    }

    public function testSortEntityManagersCycleThrows()
    {
        $this->expectException(\RuntimeException::class);
        MigrateCommand::sortEntityManagers([
            'em_a' => ['em_b'],
            'em_b' => ['em_a'],
        ]);
    }

    public function testCalledTooLate()
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('dbp_api._prepend_done', true);
        $this->expectException(\RuntimeException::class);
        $this->addQueueMessageClass($builder, 'foobar');
    }
}
