<?php

namespace Nexus\Utils;

class FieldExtractor
{
    public function __construct(private array $data) {}

    public function get(string $path, mixed $default = null): mixed
    {
        $parts = explode('.', $path);
        $current = $this->data;

        foreach ($parts as $part) {
            if ($current === null) {
                return $default;
            }

            if (is_array($current)) {
                if (is_numeric($part)) {
                    $idx = (int)$part;
                    $current = $current[$idx] ?? $default;
                } else {
                    $current = $current[$part] ?? $default;
                }
            } else {
                return $default;
            }
        }

        return $current ?? $default;
    }

    public function getString(string $path, string $default = ''): string
    {
        $value = $this->get($path, $default);
        return $value !== null ? trim((string)$value) : $default;
    }

    public function getInt(string $path, ?int $default = null): ?int
    {
        $value = $this->get($path);
        if ($value === null) {
            return $default;
        }

        return is_numeric($value) ? (int)$value : $default;
    }

    public function getList(string $path, array $default = []): array
    {
        $value = $this->get($path, $default);
        return is_array($value) ? $value : $default;
    }

    public function getFirst(string ...$paths): mixed
    {
        foreach ($paths as $path) {
            $value = $this->get($path);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }
}
