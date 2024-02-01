<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\User;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class UserAttributeCompilerPass implements CompilerPassInterface
{
    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(UserAttributeProviderInterface::class)
            ->addTag('user.user_attribute_provider');
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
