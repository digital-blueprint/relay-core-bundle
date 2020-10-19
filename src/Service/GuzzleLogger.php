<?php

declare(strict_types=1);

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Helpers\Tools;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class GuzzleLogger implements LoggerInterface
{
    use LoggerTrait;

    private $logger;

    public function __construct(LoggerInterface $guzzleLogger)
    {
        $this->logger = $guzzleLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $message = Tools::filterErrorMessage($message);
        $this->logger->log($level, $message, $context);
    }
}
