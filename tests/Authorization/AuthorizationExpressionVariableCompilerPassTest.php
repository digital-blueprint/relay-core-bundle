<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Tests\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationExpressionChecker;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationExpressionVariableCompilerPass;
use Dbp\Relay\CoreBundle\Authorization\AuthorizationExpressionVariableProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class AuthorizationExpressionVariableCompilerPassTest extends TestCase
{
    public function testCompilerPass(): void
    {
        $builder = new ContainerBuilder(new ParameterBag());
        $builder->register(AuthorizationExpressionChecker::class);
        AuthorizationExpressionVariableCompilerPass::register($builder);
        $pass = new AuthorizationExpressionVariableCompilerPass();
        $pass->process($builder);
        $this->assertTrue(true);
    }

    public function testTaggedProviderIsWired(): void
    {
        $builder = new ContainerBuilder(new ParameterBag());
        $builder->register(AuthorizationExpressionChecker::class)->setPublic(true);

        // Register a tagged provider service
        $builder->register('test.provider', TestVariableProvider::class)
            ->addTag('dbp.relay.core.authz_expression_variable')
            ->setPublic(true);

        AuthorizationExpressionVariableCompilerPass::register($builder);
        $builder->compile();

        $checkerDefinition = $builder->getDefinition(AuthorizationExpressionChecker::class);
        $methodCalls = $checkerDefinition->getMethodCalls();

        $addProviderCalls = array_filter($methodCalls, fn ($call) => $call[0] === 'addExpressionVariableProvider');
        $this->assertCount(1, $addProviderCalls);
    }

    public function testAutoconfigurationTagsProvider(): void
    {
        $builder = new ContainerBuilder(new ParameterBag());
        $builder->register(AuthorizationExpressionChecker::class)->setPublic(true);

        AuthorizationExpressionVariableCompilerPass::register($builder);

        // Register a provider via autoconfiguration (no explicit tag)
        $builder->register('test.provider', TestVariableProvider::class)
            ->setAutoconfigured(true)
            ->setPublic(true);

        $builder->compile();

        $checkerDefinition = $builder->getDefinition(AuthorizationExpressionChecker::class);
        $methodCalls = $checkerDefinition->getMethodCalls();

        $addProviderCalls = array_filter($methodCalls, fn ($call) => $call[0] === 'addExpressionVariableProvider');
        $this->assertCount(1, $addProviderCalls);
    }
}

class TestVariableProvider implements AuthorizationExpressionVariableProviderInterface
{
    public function getName(): string
    {
        return 'testVar';
    }

    public function getValue(): mixed
    {
        return new \stdClass();
    }
}
