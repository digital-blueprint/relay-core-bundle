<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\Authorization;

use Dbp\Relay\CoreBundle\User\UserAttributeMuxer;
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
     * @var UserAttributeMuxer
     */
    private $userAttributeMuxer;

    public function __construct(UserAttributeMuxer $userAttributeMuxer)
    {
        parent::__construct();
        $this->userAttributeMuxer = $userAttributeMuxer;
    }

    protected function configure()
    {
        $this->setDescription('Shows various information about the authorization providers');
        $this->addArgument('username', InputArgument::OPTIONAL, 'username');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');

        $attrs = $this->userAttributeMuxer->getAvailableAttributes();
        $all = [];
        $default = new \stdClass();
        sort($attrs, SORT_STRING | SORT_FLAG_CASE);
        foreach ($attrs as $attr) {
            $all[$attr] = $this->userAttributeMuxer->getAttribute($username, $attr, $default);
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
