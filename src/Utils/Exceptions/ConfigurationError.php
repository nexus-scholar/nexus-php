<?php

declare(strict_types=1);

namespace Nexus\Utils\Exceptions;

class ConfigurationError extends SLRException
{
    public readonly ?string $configKey;

    public function __construct(
        string $message = "Configuration error",
        ?string $configKey = null,
        array $kwargs = []
    ) {
        $details = [];
        foreach ($kwargs as $key => $value) {
            $details[$key] = $value;
        }
        if ($configKey !== null) {
            $details['config_key'] = $configKey;
        }
        parent::__construct($message, $details);
        $this->configKey = $configKey;
    }
}
