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
        $description = 'APP_SECRET should be set';
        $secret = $this->parameters->get('kernel.secret');
        if (!is_string($secret) || trim($secret) === '') {
            return new CheckResult($description, CheckResult::STATUS_FAILURE, 'APP_SECRET is not set');
        } else {
            return new CheckResult($description, CheckResult::STATUS_SUCCESS);
        }
    }

    private function checkAppDebug(): CheckResult
    {
        $description = "APP_ENV should be set to 'prod'";
        $debug = $this->parameters->get('kernel.debug');
        if (!is_bool($debug) || $debug) {
            return new CheckResult($description, CheckResult::STATUS_WARNING, 'Debugging is enabled, not suitable for production!');
        } else {
            return new CheckResult($description, CheckResult::STATUS_SUCCESS);
        }
    }

    public function check(): array
    {
        $results = [];
        $results[] = $this->checkAppSecret();
        $results[] = $this->checkAppDebug();

        return $results;
    }
}
