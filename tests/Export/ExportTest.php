<?php

namespace Nexus\Tests\Export;

use Nexus\Export\CsvExporter;
use Nexus\Export\BibtexExporter;
use Nexus\Export\RisExporter;
use Nexus\Export\JsonlExporter;
use Nexus\Export\JsonExporter;
use Nexus\Models\Document;
use Nexus\Models\Author;
use Nexus\Models\ExternalIds;
use Nexus\Models\DocumentCluster;
use PHPUnit\Framework\TestCase;

class ExportTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nexus_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    private function createTestDocument(array $overrides = []): Document
    {
        return new Document(
            title: $overrides['title'] ?? 'Test Paper',
            year: $overrides['year'] ?? 2023,
            provider: $overrides['provider'] ?? 'openalex',
            providerId: $overrides['providerId'] ?? 'W1234567890',
            externalIds: $overrides['externalIds'] ?? new ExternalIds(
                doi: '10.1234/test',
                arxivId: '2301.12345'
            ),
            abstract: $overrides['abstract'] ?? 'This is a test abstract.',
            authors: $overrides['authors'] ?? [
                new Author(familyName: 'Smith', givenName: 'John'),
                new Author(familyName: 'Doe', givenName: 'Jane'),
            ],
            venue: $overrides['venue'] ?? 'Test Journal',
            url: $overrides['url'] ?? 'https://example.com/paper',
            citedByCount: $overrides['citedByCount'] ?? 42
        );
    }

    public function testCsvExporterCreatesFile(): void
    {
        $exporter = new CsvExporter($this->tempDir);
        $doc = $this->createTestDocument();

        $path = $exporter->exportDocuments([$doc], 'test');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.csv', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('Test Paper', $content);
        $this->assertStringContainsString('2023', $content);
        $this->assertStringContainsString('Smith', $content);
    }

    public function testCsvExporterWithEmptyDocuments(): void
    {
        $exporter = new CsvExporter($this->tempDir);

        $path = $exporter->exportDocuments([], 'empty');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('title', $content);
    }

    public function testBibtexExporterCreatesFile(): void
    {
        $exporter = new BibtexExporter($this->tempDir);
        $doc = $this->createTestDocument();

        $path = $exporter->exportDocuments([$doc], 'references');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.bib', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('@article', $content);
        $this->assertStringContainsString('title = {{Test Paper}}', $content);
        $this->assertStringContainsString('author = {John Smith and Jane Doe}', $content);
    }

    public function testBibtexExporterWithArxiv(): void
    {
        $exporter = new BibtexExporter($this->tempDir);
        $doc = new Document(
            title: 'Test Paper',
            year: 2023,
            provider: 'arxiv',
            providerId: 'arxiv:2301.12345',
            externalIds: new ExternalIds(doi: null, arxivId: '2301.12345'),
            abstract: 'This is a test abstract.',
            authors: [
                new Author(familyName: 'Smith', givenName: 'John'),
                new Author(familyName: 'Doe', givenName: 'Jane'),
            ],
            venue: null,
            url: 'https://arxiv.org/abs/2301.12345',
            citedByCount: 42
        );

        $path = $exporter->exportDocuments([$doc], 'references');

        $content = file_get_contents($path);
        $this->assertStringContainsString('@misc', $content);
        $this->assertStringContainsString('eprint = {2301.12345}', $content);
    }

    public function testRisExporterCreatesFile(): void
    {
        $exporter = new RisExporter($this->tempDir);
        $doc = $this->createTestDocument();

        $path = $exporter->exportDocuments([$doc], 'references');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.ris', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('TY  - JOUR', $content);
        $this->assertStringContainsString('TI  - Test Paper', $content);
        $this->assertStringContainsString('AU  - Smith, John', $content);
        $this->assertStringContainsString('ER  -', $content);
    }

    public function testJsonlExporterCreatesFile(): void
    {
        $exporter = new JsonlExporter($this->tempDir);
        $doc = $this->createTestDocument();

        $path = $exporter->exportDocuments([$doc], 'documents');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.jsonl', $path);

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertCount(1, $lines);

        $data = json_decode($lines[0], true);
        $this->assertEquals('Test Paper', $data['title']);
        $this->assertEquals(2023, $data['year']);
        $this->assertEquals('10.1234/test', $data['external_ids']['doi']);
    }

    public function testJsonExporterCreatesFile(): void
    {
        $exporter = new JsonExporter($this->tempDir);
        $doc = $this->createTestDocument();

        $path = $exporter->exportDocuments([$doc], 'documents');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.json', $path);

        $content = file_get_contents($path);
        $this->assertStringStartsWith('[', trim($content));
        $this->assertStringEndsWith(']', trim($content));

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Test Paper', $data[0]['title']);
    }

    public function testExportClusters(): void
    {
        $exporter = new CsvExporter($this->tempDir);

        $doc1 = $this->createTestDocument(['title' => 'Paper 1', 'providerId' => 'W1']);
        $doc2 = $this->createTestDocument(['title' => 'Paper 2', 'providerId' => 'W2']);

        $cluster = new DocumentCluster(
            clusterId: 1,
            representative: $doc1,
            members: [$doc1, $doc2],
            allDois: ['10.1234/test'],
            allArxivIds: ['2301.12345'],
            providerCounts: ['openalex' => 2]
        );

        $path = $exporter->exportClusters([$cluster], 'clusters');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('Paper 1', $content);
    }

    public function testFileExtensionProperty(): void
    {
        $this->assertEquals('csv', (new CsvExporter())->getFileExtension());
        $this->assertEquals('bib', (new BibtexExporter())->getFileExtension());
        $this->assertEquals('ris', (new RisExporter())->getFileExtension());
        $this->assertEquals('jsonl', (new JsonlExporter())->getFileExtension());
        $this->assertEquals('json', (new JsonExporter())->getFileExtension());
    }
}
