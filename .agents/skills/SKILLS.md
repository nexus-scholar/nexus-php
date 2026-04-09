---
name: nexus-php
description: Advanced scholarly data management including provider integration, citation snowballing, deduplication, and exporters. Use this skill when implementing new academic data sources or complex research workflows.
---
# Nexus PHP Agent Skills (Super-Skill)

This package (`nexus/nexus-php`) is the backbone for scholarly data access. It handles provider integration, citation networking, and data normalization.

## 📚 Advanced References
- **[Providers & Mapping](references/providers.md)**: Query translation, normalization (OpenAlex/Crossref), and pagination.
- **[Deduplication Strategies](references/deduplication.md)**: Conservative (DOI) and Fuzzy (Title-based) matching.
- **[Citation Snowballing](references/snowballing.md)**: Forward/Backward citation expansion and BFS strategies.
- **[Data Exporters](references/exporters.md)**: RIS, BibTeX, and CSV mapping standards.

## Skill: Add a Provider
**Trigger:** User asks to add support for a new academic database (e.g., Scopus, CORE, BASE).

**Steps:**
1. Create `src/Providers/{Name}Provider.php` extending `Nexus\Providers\BaseProvider`.
2. Implement `translateQuery(Query $query): array`.
3. Implement `normalizeResponse(mixed $raw): ?Document`.
4. Implement `search(Query $query): Generator`.
5. Register in `ProviderFactory::createProvider()`.
6. **Advanced Details**: See [references/providers.md](references/providers.md).

## Skill: Implement Snowballing on a Provider
**Trigger:** Provider needs to support forward/backward citations.

**Steps:**
1. Implement `Nexus\Core\SnowballProviderInterface` on the provider class.
2. Implement `getCitingDocuments(Document $document, int $limit = 100): Generator`.
3. Implement `getReferencedDocuments(Document $document, int $limit = 50): Generator`.
4. **Advanced Details**: See [references/snowballing.md](references/snowballing.md).

## Skill: Add an Exporter
**Trigger:** User wants to export results to a new format (e.g., RIS, BibTeX, Markdown).

**Steps:**
1. Create `src/Export/{Format}Exporter.php` implementing `Nexus\Export\ExporterInterface`.
2. Extend `Nexus\Export\BaseExporter` for helpers.
3. **Advanced Details**: See [references/exporters.md](references/exporters.md).

## Skill: Add a Deduplication Strategy
**Trigger:** Need a new way to identify duplicate papers.

**Steps:**
1. Create `src/Dedup/{Name}Strategy.php` extending `Nexus\Dedup\DeduplicationStrategy`.
2. Implement `deduplicate(array $documents): array`.
3. Update `DeduplicationStrategyName` enum.
4. **Advanced Details**: See [references/deduplication.md](references/deduplication.md).
