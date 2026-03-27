<?php

namespace Nexus\Models;

class SnowballConfig
{
    public function __construct(
        public bool $forward = true,
        public bool $backward = true,
        public int $maxCitations = 100,
        public int $maxReferences = 50,
        public int $depth = 1
    ) {}
}
