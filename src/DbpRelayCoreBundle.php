<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle;

use Dbp\Relay\CoreBundle\Cron\CronCompilerPass;
use Dbp\Relay\CoreBundle\HealthCheck\HealthCheckCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpRelayCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        HealthCheckCompilerPass::register($container);
        CronCompilerPass::register($container);
    }
}
