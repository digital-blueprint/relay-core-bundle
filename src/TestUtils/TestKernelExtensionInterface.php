<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\TestUtils;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

interface TestKernelExtensionInterface
{
    public function registerBundles(): iterable;

    public function configureRoutes(RoutingConfigurator $routes): void;

    public function configureContainer(ContainerConfigurator $container): void;
}
