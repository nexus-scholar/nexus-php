<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ValidationError extends SLRException
{
    public readonly ?string $field;

    public function __construct(
        string $message = 'Validation failed',
        ?string $field = null,
        array $kwargs = []
    ) {
        $details = [];
        foreach ($kwargs as $key => $value) {
            $details[$key] = $value;
        }
        if ($field !== null) {
            $details['field'] = $field;
        }
        parent::__construct($message, $details);
        $this->field = $field;
    }
}
