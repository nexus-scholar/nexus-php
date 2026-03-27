<?php

namespace Nexus\Models;

class Author
{
    public function __construct(
        public string $familyName,
        public ?string $givenName = null,
        public ?string $orcid = null
    ) {}

    public function getFullName(): string
    {
        if ($this->givenName) {
            return "{$this->givenName} {$this->familyName}";
        }

        return $this->familyName;
    }

    public function toArray(): array
    {
        return [
            'family_name' => $this->familyName,
            'given_name' => $this->givenName,
            'orcid' => $this->orcid,
            'full_name' => $this->getFullName(),
        ];
    }
}
