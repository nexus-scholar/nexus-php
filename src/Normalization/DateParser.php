<?php

declare(strict_types=1);

namespace Nexus\Normalization;

use DateTime;
use Exception;

class DateParser
{
    public static function extractYear(mixed $dateValue): ?int
    {
        if ($dateValue === null) {
            return null;
        }

        if (is_int($dateValue)) {
            if ($dateValue >= 1900 && $dateValue <= 2100) {
                return $dateValue;
            }
            return null;
        }

        if (is_array($dateValue)) {
            $year = $dateValue['year'] ?? $dateValue['Year'] ?? null;
            if ($year !== null) {
                return self::extractYear($year);
            }
            return null;
        }

        if (is_string($dateValue)) {
            if (preg_match('/\b(19|20)\d{2}\b/', $dateValue, $matches)) {
                $year = (int) $matches[0];
                if ($year >= 1900 && $year <= 2100) {
                    return $year;
                }
            }
        }

        return null;
    }

    public static function parseDate(mixed $dateValue): ?DateTime
    {
        if (empty($dateValue)) {
            return null;
        }

        if ($dateValue instanceof DateTime) {
            return $dateValue;
        }

        $formats = [
            'Y-m-d',
            'Y/m/d',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:s\Z',
            'd/m/Y',
        ];

        $dateStr = (string) $dateValue;

        foreach ($formats as $format) {
            try {
                $date = DateTime::createFromFormat($format, $dateStr);
                if ($date !== false) {
                    return $date;
                }
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }
}
