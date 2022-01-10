<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CronCompilerPass implements CompilerPassInterface
{
    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(CronJobInterface::class)->addTag('dbp.relay.cron_job');
        $container->addCompilerPass(new CronCompilerPass());
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CronCommand::class)) {
            return;
        }
        $definition = $container->findDefinition(CronCommand::class);
        $taggedServices = $container->findTaggedServiceIds('dbp.relay.cron_job');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addJob', [new Reference($id)]);
        }
    }
}
