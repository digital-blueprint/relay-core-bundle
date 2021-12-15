<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HealthCheckCompilerPass implements CompilerPassInterface
{
    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(CheckInterface::class)->addTag('dbp.relay.health_check');
        $container->addCompilerPass(new HealthCheckCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(HealthCheckCommand::class)) {
            return;
        }
        $definition = $container->findDefinition(HealthCheckCommand::class);
        $taggedServices = $container->findTaggedServiceIds('dbp.relay.health_check');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addCheck', [new Reference($id)]);
        }
    }
}
