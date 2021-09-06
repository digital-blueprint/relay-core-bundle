<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Cron;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CronCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:cron|dbp:cron';

    /** @var EventDispatcherInterface */
    private $dispatcher;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    public function setCache(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    protected function configure()
    {
        $this->setDescription('Runs various tasks which need to be executed periodically');
    }

    public function createCronEvent(): CronEvent
    {
        $fetchAndUpdateRunState = function (\DateTimeInterface $currentTime): ?\DateTimeInterface {
            // Store the previous run time in the cache and fetch from there
            assert($this->cachePool !== null);
            $item = $this->cachePool->getItem('cron-previous-run');
            $value = $item->get();
            $previousRun = null;
            if ($value !== null) {
                $previousRun = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp($value);
                if ($previousRun > $currentTime) {
                    // Something is wrong, cap at the current time
                    $previousRun = $currentTime;
                }
            }
            $item->set($currentTime->getTimestamp());
            if ($this->cachePool->save($item) === false) {
                throw new \RuntimeException('Saving cron timestamp failed');
            }

            return $previousRun;
        };

        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        // round to full seconds, so we have the same resolution for both date times
        $currentTime = $currentTime->setTimestamp($currentTime->getTimestamp());
        $previousRunTime = $fetchAndUpdateRunState($currentTime);
        $event = new CronEvent($previousRunTime, $currentTime);
        $event->setLogger($this->logger);

        return $event;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $event = $this->createCronEvent();
        $this->dispatcher->dispatch($event, CronEvent::NAME);

        // Run 'cache:pool:prune' every hour
        if ($event->isDue('cache-prune', '* * * * *')) {
            $app = $this->getApplication();
            assert($app !== null);
            $command = $app->find('cache:pool:prune');
            $pruneInput = new ArrayInput([]);
            $pruneOutput = new BufferedOutput();

            return $command->run($pruneInput, $pruneOutput);
        }

        return 0;
    }
}
