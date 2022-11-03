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
        if ($username === null) {
            // No username, list all providers
            $output->writeln("<fg=blue;options=bold>Attributes available for all provider</>\n");
            $providers = $this->provider->getAuthorizationDataProviders();
            foreach ($providers as $provider) {
                $output->writeln('<fg=green;options=bold>['.get_class($provider).']</>');
                foreach ($provider->getAvailableAttributes() as $attr) {
                    $output->writeln($attr);
                }
                $output->writeln('');
            }

            // TODO: list all attributes
        } else {
            // get all available attributes for a user per provider
            $output->writeln('<fg=blue;options=bold>Attributes for user "'.$username."\":</>\n");
            $providers = $this->provider->getAuthorizationDataProviders();
            foreach ($providers as $provider) {
                $output->writeln('<fg=green;options=bold>['.get_class($provider).']</>');
                foreach ($provider->getUserAttributes($username) as $attr => $value) {
                    $output->writeln($attr.'='.json_encode($value));
                }
                $output->writeln('');
            }

            // TODO: list all attributes
        }

        return 0;
    }
}
