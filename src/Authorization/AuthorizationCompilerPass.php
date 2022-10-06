<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AuthorizationCompilerPass implements CompilerPassInterface
{
    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(AuthorizationDataProviderInterface::class)
            ->addTag('auth.authorization_data_provider');
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
