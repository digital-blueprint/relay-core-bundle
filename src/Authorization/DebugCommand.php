<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $defaultName = 'dbp:relay:core:auth-debug';

    /**
     * @var AuthorizationDataProviderProvider
     */
    private $provider;

    public function __construct(AuthorizationDataProviderProvider $provider)
    {
        parent::__construct();
        $this->provider = $provider;
    }

    protected function configure()
    {
        $this->setDescription('Shows various information about the authorization providers');
        $this->addArgument('username', InputArgument::OPTIONAL, 'username');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        // Fetch all attributes first (to get potential log spam first)
        $providers = $this->provider->getAuthorizationDataProviders();
        $mux = new AuthorizationDataMuxer($providers);
        $attrs = $mux->getAvailableAttributes();
        $all = [];
        $default = new \stdClass();
        sort($attrs, SORT_STRING | SORT_FLAG_CASE);
        foreach ($attrs as $attr) {
            $all[$attr] = $mux->getCustomAttribute($username, $attr, $default);
        }

        // Now print them out
        $output->writeln('<fg=blue;options=bold>[Authorization attributes]</>');
        foreach ($all as $attr => $value) {
            if ($value === $default) {
                $output->writeln('<fg=green;options=bold>'.$attr.'</> = <fg=magenta;options=bold>\<N/A\></>');
            } else {
                $output->writeln('<fg=green;options=bold>'.$attr.'</> = '.json_encode($value));
            }
        }

        return 0;
    }
}
