<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SystemCheck implements CheckInterface
{
    public function getName(): string
    {
        return 'core.system';
    }

    private function checkDNS(): CheckResult
    {
        $result = new CheckResult('Check if DNS is working');
        $ok = !empty(dns_get_record('orf.at'));
        if ($ok) {
            $result->set(CheckResult::STATUS_SUCCESS);
        } else {
            $result->set(CheckResult::STATUS_FAILURE, 'Failed to look up IP for orf.at');
        }

        return $result;
    }

    private function checkTLS(): CheckResult
    {
        $result = new CheckResult('Check if TLS is working');
        $client = new Client();
        try {
            $client->head('https://www.tugraz.at/');
        } catch (GuzzleException $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage());

            return $result;
        }

        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        $results = [];
        $results[] = $this->checkDNS();
        $results[] = $this->checkTLS();

        return $results;
    }
}
