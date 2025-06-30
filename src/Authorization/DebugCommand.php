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

    /**
     * @var UserAttributeMuxer
     */
    private $userAttributeMuxer;

    public function __construct(UserAttributeMuxer $userAttributeMuxer)
    {
        parent::__construct();
        $this->userAttributeMuxer = $userAttributeMuxer;
    }

    protected function configure(): void
    {
        $this->setName('dbp:relay:core:auth-debug');
        $this->setDescription('Shows various information about the authorization providers');
        $this->addArgument('attribute', InputArgument::REQUIRED, 'the user attribute name to query');
        $this->addArgument('user_id', InputArgument::OPTIONAL, 'the user identifier to query');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $attribute = $input->getArgument('attribute');
            $userId = $input->getArgument('user_id');

            $attrs = [$attribute];
            $all = [];
            $default = new \stdClass();
            sort($attrs, SORT_STRING | SORT_FLAG_CASE);
            foreach ($attrs as $attr) {
                $all[$attr] = $this->userAttributeMuxer->getAttribute($userId, $attr, $default);
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
        } catch (\Throwable $e) {
            $output->writeln('<fg=red;options=bold>An error occurred: '.$e->getMessage().'</>');

            return 1;
        }

        return 0;
    }
}
