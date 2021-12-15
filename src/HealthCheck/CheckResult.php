<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreBundle\HealthCheck;

class CheckResult
{
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_WARNING = 'WARNING';
    public const STATUS_FAILURE = 'FAILURE';

    private $description;
    private $status;
    private $message;
    private $extra;

    public function __construct(string $description, string $status, ?string $message = null, ?array $extra = null)
    {
        $this->description = $description;
        $this->status = $status;
        $this->message = $message;
        $this->extra = $extra;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }
}
