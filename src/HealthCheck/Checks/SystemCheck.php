<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
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
        $description = 'Check if DNS is working';
        $ok = !empty(dns_get_record('orf.at'));
        if ($ok) {
            return new CheckResult($description, CheckResult::STATUS_SUCCESS);
        } else {
            return new CheckResult($description, CheckResult::STATUS_FAILURE, 'Failed to look up IP for orf.at');
        }
    }

    private function checkTLS(): CheckResult
    {
        $description = 'Check if TLS is working';
        $client = new Client();
        try {
            $client->head('https://www.tugraz.at/');
        } catch (GuzzleException $e) {
            return new CheckResult($description, CheckResult::STATUS_FAILURE, $e->getMessage());
        }

        return new CheckResult($description, CheckResult::STATUS_SUCCESS);
    }

    public function check(): array
    {
        $results = [];
        $results[] = $this->checkDNS();
        $results[] = $this->checkTLS();

        return $results;
    }
}
