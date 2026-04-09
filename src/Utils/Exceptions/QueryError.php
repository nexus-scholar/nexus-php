<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class QueryError extends SLRException
{
    public readonly ?string $query;

    public function __construct(
        string $message = 'Query error',
        ?string $query = null,
        array $kwargs = []
    ) {
        $details = [];
        foreach ($kwargs as $key => $value) {
            $details[$key] = $value;
        }
        if ($query !== null) {
            $details['query'] = $query;
        }
        parent::__construct($message, $details);
        $this->query = $query;
    }
}
