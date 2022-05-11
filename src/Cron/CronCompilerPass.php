<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CronCompilerPass implements CompilerPassInterface
{
    private const TAG = 'dbp.relay.core.cron_job';

    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(CronJobInterface::class)->addTag(self::TAG);
        $container->addCompilerPass(new CronCompilerPass());
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(CronCommand::class)) {
            return;
        }
        $definition = $container->findDefinition(CronCommand::class);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addJob', [new Reference($id)]);
        }
    }
}
