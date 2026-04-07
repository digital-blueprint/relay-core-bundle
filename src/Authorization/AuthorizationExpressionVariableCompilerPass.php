<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationExpressionVariableCompilerPass implements CompilerPassInterface
{
    private const TAG = 'dbp.relay.core.authz_expression_variable';

    public static function register(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(AuthorizationExpressionVariableProviderInterface::class)
            ->addTag(self::TAG);
        $container->addCompilerPass(new self());
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AuthorizationExpressionChecker::class)) {
            return;
        }
        $definition = $container->findDefinition(AuthorizationExpressionChecker::class);
        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $definition->addMethodCall('addExpressionVariableProvider', [new Reference($id)]);
        }
    }
}
