<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck\Checks;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Uid\Uuid;

class CacheCheck implements CheckInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getName(): string
    {
        return 'core.cache';
    }

    public function check(CheckOptions $options): array
    {
        $result = new CheckResult('Check if the application cache works');

        $key = Uuid::v4()->toRfc4122();
        $value = Uuid::v4()->toRfc4122();
        $result->set(CheckResult::STATUS_SUCCESS);
        try {
            $item = $this->adapter->getItem($key);
            if ($item->isHit()) {
                throw new \RuntimeException('cache returned hit for random item');
            }
            // add expiration, so it gets removed eventually, even if things fail below
            $item->expiresAfter(3600);
            $item->set($value);
            if (!$this->adapter->save($item)) {
                throw new \RuntimeException('saving an item to the cache failed');
            }
            $item = $this->adapter->getItem($key);
            if (!$item->isHit() || $item->get() !== $value) {
                throw new \RuntimeException('fetching from the cache failed');
            }
            if (!$this->adapter->deleteItem($key)) {
                throw new \RuntimeException('deleting an item from the cache failed');
            }
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);
        }

        return [$result];
    }
}
