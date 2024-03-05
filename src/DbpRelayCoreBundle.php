<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle;

use Dbp\Relay\CoreBundle\Auth\AuthenticatorCompilerPass;
use Dbp\Relay\CoreBundle\Cron\CronCompilerPass;
use Dbp\Relay\CoreBundle\HealthCheck\HealthCheckCompilerPass;
use Dbp\Relay\CoreBundle\User\UserAttributeCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbpRelayCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        HealthCheckCompilerPass::register($container);
        CronCompilerPass::register($container);
        AuthenticatorCompilerPass::register($container);
        UserAttributeCompilerPass::register($container);
    }
}
