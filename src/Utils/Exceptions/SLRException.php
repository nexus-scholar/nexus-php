<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

use DateTimeImmutable;
use Exception;

class SLRException extends Exception
{
    public array $details;

    public DateTimeImmutable $timestamp;

    public function __construct(string $message, ?array $details = null)
    {
        parent::__construct($message);
        $this->details = $details ?? [];
        $this->timestamp = new DateTimeImmutable;
    }

    public function __toString(): string
    {
        if (! empty($this->details)) {
            $detailsStr = implode(', ', array_map(
                fn ($k, $v) => "{$k}={$v}",
                array_keys($this->details),
                array_values($this->details)
            ));

            return "{$this->getMessage()} ({$detailsStr})";
        }

        return $this->getMessage();
    }

    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'details' => $this->details,
            'timestamp' => $this->timestamp->format(DateTimeImmutable::ATOM),
        ];
    }
}
