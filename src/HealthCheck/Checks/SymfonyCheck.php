<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SymfonyCheck implements CheckInterface
{
    private $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return 'core.symfony';
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

    public function check(): array
    {
        $results = [];
        $results[] = $this->checkAppSecret();
        $results[] = $this->checkAppDebug();

        return $results;
    }
}
