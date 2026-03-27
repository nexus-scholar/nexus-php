<?php

return [
    'mailto' => env('NEXUS_MAILTO', 'your-email@example.com'),

    'year_min' => env('NEXUS_YEAR_MIN', 2020),

    'year_max' => env('NEXUS_YEAR_MAX', 2026),

    'language' => env('NEXUS_LANGUAGE', 'en'),

    'providers' => [
        'openalex' => [
            'enabled' => env('NEXUS_OPENALEX_ENABLED', true),
            'rate_limit' => env('NEXUS_OPENALEX_RATE_LIMIT', 5.0),
            'timeout' => env('NEXUS_OPENALEX_TIMEOUT', 30),
        ],
        'crossref' => [
            'enabled' => env('NEXUS_CROSSREF_ENABLED', true),
            'rate_limit' => env('NEXUS_CROSSREF_RATE_LIMIT', 1.0),
            'timeout' => env('NEXUS_CROSSREF_TIMEOUT', 30),
        ],
        'arxiv' => [
            'enabled' => env('NEXUS_ARXIV_ENABLED', true),
            'rate_limit' => env('NEXUS_ARXIV_RATE_LIMIT', 0.5),
            'timeout' => env('NEXUS_ARXIV_TIMEOUT', 30),
        ],
        's2' => [
            'enabled' => env('NEXUS_S2_ENABLED', true),
            'rate_limit' => env('NEXUS_S2_RATE_LIMIT', 1.0),
            'timeout' => env('NEXUS_S2_TIMEOUT', 30),
        ],
        'pubmed' => [
            'enabled' => env('NEXUS_PUBMED_ENABLED', true),
            'rate_limit' => env('NEXUS_PUBMED_RATE_LIMIT', 3.0),
            'timeout' => env('NEXUS_PUBMED_TIMEOUT', 30),
            'api_key' => env('NEXUS_PUBMED_API_KEY'),
        ],
        'doaj' => [
            'enabled' => env('NEXUS_DOAJ_ENABLED', true),
            'rate_limit' => env('NEXUS_DOAJ_RATE_LIMIT', 2.0),
            'timeout' => env('NEXUS_DOAJ_TIMEOUT', 30),
        ],
        'ieee' => [
            'enabled' => env('NEXUS_IEEE_ENABLED', false),
            'rate_limit' => env('NEXUS_IEEE_RATE_LIMIT', 1.0),
            'timeout' => env('NEXUS_IEEE_TIMEOUT', 30),
            'api_key' => env('NEXUS_IEEE_API_KEY'),
        ],
    ],

    'deduplication' => [
        'strategy' => env('NEXUS_DEDUP_STRATEGY', 'conservative'),
        'fuzzy_threshold' => env('NEXUS_DEDUP_FUZZY_THRESHOLD', 97),
        'max_year_gap' => env('NEXUS_DEDUP_MAX_YEAR_GAP', 1),
    ],

    'cache' => [
        'ttl' => env('NEXUS_CACHE_TTL', 3600),
        'store' => env('NEXUS_CACHE_STORE', 'default'),
    ],

    'rate_limiter' => [
        'attempts' => env('NEXUS_RATE_LIMIT_ATTEMPTS', 60),
        'decay_seconds' => env('NEXUS_RATE_LIMIT_DECAY', 60),
    ],

    'queue' => [
        'connection' => env('NEXUS_QUEUE_CONNECTION', 'default'),
        'name' => env('NEXUS_QUEUE_NAME', 'nexus'),
    ],

    'logging' => [
        'enabled' => env('NEXUS_LOGGING_ENABLED', true),
    ],
];
