<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Internal;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Uid\Uuid;

class CacheWarmer implements CacheWarmerInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir): array
    {
        // This is a workaround for https://github.com/symfony/symfony/issues/32569
        // Create a dummy cache entry, so that the cache database and table get created,
        // so we don't get spammed with errors on cache misses
        $key = Uuid::v4()->toRfc4122();
        $item = $this->adapter->getItem($key);
        $item->set(true);
        $this->adapter->save($item);
        $this->adapter->deleteItem($key);

        return [];
    }
}
