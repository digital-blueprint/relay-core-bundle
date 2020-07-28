<?php

namespace DBP\API\CoreBundle\Service;

use DBP\API\CoreBundle\Helpers\Tools;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class GuzzleLogger implements LoggerInterface
{

    private $logger;

    public function __construct(LoggerInterface $guzzleLogger)
    {
        $this->logger = $guzzleLogger;
    }

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = array())
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = array())
    {
        $this->logger->alert($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = array())
    {
        $this->logger->critical($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = array())
    {
        $this->logger->error($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = array())
    {
        $this->logger->warning($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = array())
    {
        $this->logger->notice($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = array())
    {
        $this->logger->info($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = array())
    {
        $this->logger->debug($message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        $message = Tools::filterErrorMessage($message);
        $this->logger->log($level, $message, $context);
    }

    // --------------------
    public function getClientHandler()
    {
        return Middleware::log(
            $this,
            new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')
        );
    }
}