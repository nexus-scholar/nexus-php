<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class DeduplicationError extends SLRException
{
    public function __construct(string $message = 'Deduplication failed', array $kwargs = [])
    {
        $details = [];
        foreach ($kwargs as $key => $value) {
            $details[$key] = $value;
        }
        parent::__construct($message, $details);
    }
}
