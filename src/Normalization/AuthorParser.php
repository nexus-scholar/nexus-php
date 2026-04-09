<?php

declare(strict_types=1);

namespace Nexus\Normalization;

use Nexus\Models\Author;

class AuthorParser
{
    public static function parseAuthorName(string $name): array
    {
        if (empty($name)) {
            return ['family' => 'Unknown', 'given' => null];
        }

        $name = trim($name);

        if (str_contains($name, ',')) {
            $parts = explode(',', $name, 2);

            return [
                'family' => trim($parts[0]),
                'given' => isset($parts[1]) ? trim($parts[1]) : null,
            ];
        }

        $parts = preg_split('/\s+/', $name);
        if (count($parts) === 1) {
            return ['family' => $parts[0], 'given' => null];
        }

        return [
            'family' => array_pop($parts),
            'given' => implode(' ', $parts),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $authorsData
     * @return Author[]
     */
    public static function parseAuthors(
        array $authorsData,
        string $nameField = 'name',
        ?string $familyField = null,
        ?string $givenField = null,
        ?string $orcidField = null
    ): array {
        $authors = [];

        foreach ($authorsData as $authorData) {
            if (is_string($authorData)) {
                $parsed = self::parseAuthorName($authorData);
                $authors[] = new Author(
                    familyName: $parsed['family'] ?? 'Unknown',
                    givenName: $parsed['given']
                );
            } elseif (is_array($authorData)) {
                $family = 'Unknown';
                $given = null;

                if ($familyField !== null) {
                    $family = $authorData[$familyField] ?? 'Unknown';
                    $given = $givenField !== null ? ($authorData[$givenField] ?? null) : null;
                } else {
                    $fullName = $authorData[$nameField] ?? 'Unknown';
                    $parsed = self::parseAuthorName((string) $fullName);
                    $family = $parsed['family'] ?? 'Unknown';
                    $given = $parsed['given'];
                }

                $orcid = null;
                if ($orcidField !== null) {
                    $orcid = $authorData[$orcidField] ?? null;
                } else {
                    $orcid = $authorData['orcid']
                        ?? $authorData['ORCID']
                        ?? $authorData['orcid_id']
                        ?? null;
                }

                $authors[] = new Author(
                    familyName: (string) $family,
                    givenName: $given,
                    orcid: $orcid
                );
            }
        }

        return $authors;
    }
}
