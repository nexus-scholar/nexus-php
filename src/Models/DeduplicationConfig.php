<?php

namespace Nexus\Models;

enum DeduplicationStrategyName: string
{
    case CONSERVATIVE = 'conservative';
    case SEMANTIC = 'semantic';
}

class DeduplicationConfig
{
    public function __construct(
        public DeduplicationStrategyName $strategy = DeduplicationStrategyName::CONSERVATIVE,
        public int $fuzzyThreshold = 97,
        public int $maxYearGap = 1,
        public float $semanticThreshold = 0.92,
        public string $embeddingModel = 'all-MiniLM-L6-v2',
        public bool $useEmbeddings = false
    ) {}
}
