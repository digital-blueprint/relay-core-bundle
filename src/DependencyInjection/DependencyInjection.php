<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Exception\ErrorProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

function configure(ContainerConfigurator $configurator): void
{
    $services = $configurator->services();
    $services->set('api_platform.state.error_provider')
        ->class(ErrorProvider::class)
        ->tag('api_platform.state_provider', ['key' => 'api_platform.state.error_provider']);
}
