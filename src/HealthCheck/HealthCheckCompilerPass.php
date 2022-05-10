<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HealthCheckCompilerPass implements CompilerPassInterface
{
    private const TAG = 'dbp.relay.core.health_check';

    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(CheckInterface::class)->addTag(self::TAG);
        $container->addCompilerPass(new HealthCheckCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(HealthCheckCommand::class)) {
            return;
        }
        $definition = $container->findDefinition(HealthCheckCommand::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addCheck', [new Reference($id)]);
        }
    }
}
