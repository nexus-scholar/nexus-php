# Export Module

The Export module serializes normalized document collections into standard scholarly formats for downstream use in reference managers, spreadsheets, scripts, and bibliometric tools.

## Files

- `src/Export/ExporterInterface.php`
- `src/Export/BaseExporter.php`
- `src/Export/BibtexExporter.php`
- `src/Export/RisExporter.php`
- `src/Export/CsvExporter.php`
- `src/Export/JsonExporter.php`
- `src/Export/JsonlExporter.php`

## Supported formats

- BibTeX
- RIS
- CSV
- JSON
- JSONL

## Basic examples

### BibTeX

```php
use Nexus\Export\BibtexExporter;

$bibtex = (new BibtexExporter())->export($documents);
file_put_contents('results.bib', $bibtex);
```

### RIS

```php
use Nexus\Export\RisExporter;

file_put_contents('results.ris', (new RisExporter())->export($documents));
```

### CSV

```php
use Nexus\Export\CsvExporter;

file_put_contents('results.csv', (new CsvExporter())->export($documents));
```

### JSON

```php
use Nexus\Export\JsonExporter;

file_put_contents('results.json', (new JsonExporter())->export($documents));
```

### JSONL

```php
use Nexus\Export\JsonlExporter;

file_put_contents('results.jsonl', (new JsonlExporter())->export($documents));
```

## Choosing a format

- Use **BibTeX** for LaTeX and many reference managers.
- Use **RIS** for broad reference-manager interoperability.
- Use **CSV** for spreadsheets and quick screening.
- Use **JSON** for APIs and application-level interchange.
- Use **JSONL** for streaming and data pipelines.

## Best practices

- Export canonical deduplicated documents, not raw duplicates.
- Keep one archival JSON export even if you also generate BibTeX or RIS.
- Include provenance fields when possible.
- Use CSV for review team screening workflows.
