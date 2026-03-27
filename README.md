# Nexus PHP

[![Tests](https://github.com/mbsoft31/nexus-php/actions/workflows/test.yml/badge.svg)](https://github.com/mbsoft31/nexus-php/actions/workflows/test.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.3-777bb4.svg)](https://www.php.net/)

A PHP library for systematic literature reviews (SLR) that searches multiple academic databases simultaneously, performs citation network analysis, and retrieves full-text PDFs. This is a port of the [Nexus Research Engine](https://github.com/google/nexus-api) logic.

## Features

- **Multi-Provider Search**: Search across OpenAlex, Crossref, arXiv, Semantic Scholar, PubMed, DOAJ, and IEEE Xplore.
- **Citation Analysis**: Build and analyze citation, co-citation, and bibliographic coupling networks.
- **Network Metrics**: Calculate PageRank centrality, degree centrality, k-core, and find shortest citation paths.
- **Snowballing**: Automated forward (citing) and backward (referenced) snowballing to expand your literature search.
- **PDF Retrieval**: Multi-source full-text PDF discovery and download (arXiv, OpenAlex, Semantic Scholar, Direct).
- **Visualization Export**: Export networks to GEXF (Gephi), GraphML (yEd/NetworkX), and Cytoscape.js formats.
- **Deduplication**: Built-in conservative and aggressive deduplication strategies.
- **Export**: Export results to BibTeX, RIS, CSV, JSON, and JSONL formats.
- **Laravel Ready**: First-class support for Laravel with AI agents, tools, and background jobs.

## Requirements

- PHP 8.3+
- ext-json
- ext-curl
- ext-openssl
- ext-dom

## Installation

```bash
composer require mbsoft31/nexus-php
```

The package includes high-performance graph algorithms via `mbsoft31/graph-core` and `mbsoft31/graph-algorithms`.

## Core Features

### 1. Snowballing

Expand your literature search by exploring the citation network of seed documents.

```php
use Nexus\Core\SnowballService;
use Nexus\Models\SnowballConfig;

$config = new SnowballConfig(
    forward: true,        // Fetch papers that cite the seed
    backward: true,       // Fetch papers referenced by the seed
    depth: 1              // Recursion depth
);

$service = new SnowballService($config, $openalex, $s2);
$newDocs = $service->snowball($seedDocument, $existingDocs);
```

### 2. Citation Network Analysis

Transform search results into a graph for advanced bibliometric analysis.

```php
use Nexus\CitationAnalysis\CitationGraphBuilder;
use Nexus\CitationAnalysis\NetworkAnalyzer;

$builder = new CitationGraphBuilder();
$graph = $builder->buildCitationGraph($documents);

$analyzer = new NetworkAnalyzer($graph);
$influential = $analyzer->findInfluentialPapers(limit: 10); // PageRank based
```

### 3. PDF Retrieval

Automatically find and download full-text PDFs for your documents.

```php
use Nexus\Retrieval\PDFFetcher;

$fetcher = new PDFFetcher(outputDir: './pdfs', email: 'your@email.com');
$path = $fetcher->fetch($document);

if ($path) {
    echo "PDF saved to: $path";
}
```

### 4. Visualization Export

Export your citation networks for use in external tools like Gephi or Cytoscape.

```php
use Nexus\Visualization\GraphExporter;

$exporter = new GraphExporter();
$exporter->save($graph, 'network.gexf', 'gexf');
$exporter->save($graph, 'network.json', 'cytoscape');
```

## Laravel Integration

### 1. Register the Service Provider

Add to `config/app.php` (if not using discovery):

```php
'providers' => [
    // ...
    Nexus\Laravel\NexusServiceProvider::class,
],
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=nexus-config
```

### 3. AI Agents and Tools

Nexus PHP provides AI-ready components for the Laravel AI SDK:

```php
use Nexus\Laravel\Agents\LiteratureSearchAgent;
use Nexus\Laravel\Tools\LiteratureSearchTool;

// Agent focused on literature research
$agent = LiteratureSearchAgent::make()->withProvider('openalex');
$response = $agent->prompt('Recent breakthroughs in LLM quantization');

// Tool for any AI agent
$tool = LiteratureSearchTool::make()->withProviders(['openalex', 's2']);
```

## Supported Providers

| Provider         | Alias      | API Required      | Rate Limit              |
| ---------------- | ---------- | ----------------- | ----------------------- |
| OpenAlex         | `openalex` | No                | 10/sec with mailto      |
| Crossref         | `crossref` | No                | 50/sec with mailto      |
| arXiv            | `arxiv`    | No                | 3/sec                   |
| Semantic Scholar | `s2`       | No                | 1/sec                   |
| PubMed           | `pubmed`   | No (optional key) | 3/sec (10/sec with key) |
| DOAJ             | `doaj`     | No                | 2/sec                   |
| IEEE Xplore      | `ieee`     | Yes               | 1/sec                   |

## Testing

```bash
./vendor/bin/phpunit
```

## Project Structure

```
nexus-php/
├── src/
│   ├── CitationAnalysis/  # Network analysis (PageRank, Centrality)
│   ├── Core/               # Service core and Snowballing
│   ├── Dedup/              # Deduplication logic
│   ├── Export/             # Bibliographic exports (BibTeX, RIS)
│   ├── Laravel/            # AI Agents, Tools, and Laravel core
│   ├── Models/             # Document and Config models
│   ├── Retrieval/          # PDF fetching and sources
│   ├── Visualization/      # Network exports (GEXF, Cytoscape)
│   └── Providers/          # API Provider implementations
└── tests/                  # Extensive test suite (349 tests)
```

## License

MIT
