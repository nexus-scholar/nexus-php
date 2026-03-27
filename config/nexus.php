<?php

return [
    'mailto' => 'bekhouche.mouadh@univ-oeb.dz',

    'year_min' => 2020,

    'year_max' => 2026,

    'language' => 'en',

    'providers' => [
        'openalex' => [
            'enabled' => true,
            'rate_limit' => 5.0,
            'timeout' => 30,
        ],
        'crossref' => [
            'enabled' => true,
            'rate_limit' => 1.0,
            'timeout' => 30,
        ],
        'arxiv' => [
            'enabled' => true,
            'rate_limit' => 0.5,
            'timeout' => 30,
        ],
        's2' => [
            'enabled' => true,
            'rate_limit' => 1.0,
            'timeout' => 30,
        ],
        'pubmed' => [
            'enabled' => true,
            'rate_limit' => 3.0,
            'timeout' => 30,
            'api_key' => null,
        ],
        'doaj' => [
            'enabled' => true,
            'rate_limit' => 2.0,
            'timeout' => 30,
        ],
        'ieee' => [
            'enabled' => true,
            'rate_limit' => 1.0,
            'timeout' => 30,
            'api_key' => 'sbtnjtg89w6m2hvk6rutt9wb',
        ],
    ],

    'deduplication' => [
        'strategy' => 'conservative',
        'fuzzy_threshold' => 97,
        'max_year_gap' => 1,
    ],

    'cache' => [
        'ttl' => 3600,
        'store' => 'default',
    ],

    'rate_limiter' => [
        'attempts' => 60,
        'decay_seconds' => 60,
    ],

    'queue' => [
        'connection' => 'default',
        'name' => 'nexus',
    ],

    'logging' => [
        'enabled' => true,
    ],
];
