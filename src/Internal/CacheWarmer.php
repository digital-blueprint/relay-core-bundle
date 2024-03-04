<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Internal;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Uid\Uuid;

class CacheWarmer implements CacheWarmerInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
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
        $item = $this->cachePool->getItem($key);
        $item->set(true);
        $this->cachePool->save($item);
        $this->cachePool->deleteItem($key);

        return [];
    }
}
