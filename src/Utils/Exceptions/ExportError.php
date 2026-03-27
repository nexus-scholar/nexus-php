<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ExportError extends SLRException
{
    public readonly ?string $format;

    public function __construct(
        string $message = "Export failed",
        ?string $format = null,
        array $kwargs = []
    ) {
        $details = [];
        foreach ($kwargs as $key => $value) {
            $details[$key] = $value;
        }
        if ($format !== null) {
            $details['format'] = $format;
        }
        parent::__construct($message, $details);
        $this->format = $format;
    }
}
