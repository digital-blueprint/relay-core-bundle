<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Auth;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class AuthenticatorCompilerPass implements CompilerPassInterface
{
    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(AuthenticatorInterface::class)->addTag('dbp.relay.authenticator');
        $container->addCompilerPass(new AuthenticatorCompilerPass());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ProxyAuthenticator::class)) {
            return;
        }
        $definition = $container->findDefinition(ProxyAuthenticator::class);
        $taggedServices = $container->findTaggedServiceIds('dbp.relay.authenticator');
        foreach ($taggedServices as $id => $tags) {
            if ($id === ProxyAuthenticator::class) {
                continue;
            }
            $definition->addMethodCall('addAuthenticator', [new Reference($id)]);
        }
    }
}
