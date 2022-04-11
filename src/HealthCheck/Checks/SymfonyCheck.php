<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SymfonyCheck implements CheckInterface
{
    private $parameters;
    /** @var ContainerInterface */
    private $container;

    public function __construct(ParameterBagInterface $parameters, ContainerInterface $container)
    {
        $this->parameters = $parameters;
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'core.symfony';
    }

    private function checkAllServices(): CheckResult
    {
        $result = new CheckResult('Check if all Symfony services can be initialized');
        $result->set(CheckResult::STATUS_SUCCESS);

        // This catches errors like unimplemented interfaces, cyclic dependencies and so on.
        // Otherwise we would only get those errors when the services are actually needed,
        // on specific requests/tasks at runtime.
        $container = $this->container;
        assert($container instanceof Container);
        foreach ($container->getServiceIds() as $id) {
            try {
                $container->get($id);
            } catch (\Throwable $e) {
                $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);
                break;
            }
        }

        return $result;
    }

    private function checkAppSecret(): CheckResult
    {
        $result = new CheckResult('APP_SECRET should be set');
        $secret = $this->parameters->get('kernel.secret');
        if (!is_string($secret) || trim($secret) === '') {
            $result->set(CheckResult::STATUS_FAILURE, 'APP_SECRET is not set');
        } else {
            $result->set(CheckResult::STATUS_SUCCESS);
        }

        return $result;
    }

    private function checkAppDebug(): CheckResult
    {
        $result = new CheckResult("APP_ENV should be set to 'prod'");
        $debug = $this->parameters->get('kernel.debug');
        if (!is_bool($debug) || $debug) {
            $result->set(CheckResult::STATUS_FAILURE, 'Debugging is enabled, not suitable for production!');
        } else {
            $result->set(CheckResult::STATUS_SUCCESS);
        }

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        $results = [];
        $results[] = $this->checkAppSecret();
        $results[] = $this->checkAppDebug();
        $results[] = $this->checkAllServices();

        return $results;
    }
}
