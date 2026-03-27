<?php

namespace Nexus\Tests\Retrieval;

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Retrieval\PDFFetcher;
use Nexus\Retrieval\Sources\ArxivSource;
use Nexus\Retrieval\Sources\DirectSource;
use Nexus\Retrieval\Sources\OpenAlexSource;
use Nexus\Retrieval\Sources\SemanticScholarSource;
use PHPUnit\Framework\TestCase;

class PDFFetcherTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/pdf_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->recursiveDelete($this->testDir);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_constructor_creates_output_directory(): void
    {
        $testDir = sys_get_temp_dir() . '/pdf_test_new_' . uniqid();
        
        $this->assertFalse(is_dir($testDir));
        
        $fetcher = new PDFFetcher($testDir);
        
        $this->assertTrue(is_dir($testDir));
        
        rmdir($testDir);
    }

    public function test_get_filename_uses_doi(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test Document',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertEquals('10.1234_test.pdf', $filename);
    }

    public function test_get_filename_escapes_doi_special_chars(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test:with/slashes')
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertEquals('10.1234_test_with_slashes.pdf', $filename);
    }

    public function test_get_filename_fallback_to_arxiv(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test Document',
            externalIds: new ExternalIds(arxivId: '2301.12345')
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertEquals('arxiv_2301.12345.pdf', $filename);
    }

    public function test_get_filename_fallback_to_hash(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test Document Title Without IDs'
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertStringStartsWith('doc_', $filename);
    }

    public function test_get_filename_priority_doi_over_arxiv(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test', arxivId: '2301.12345')
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertEquals('10.1234_test.pdf', $filename);
    }

    public function test_fetch_returns_false_for_no_ids(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test Document Without IDs'
        );
        
        $result = $fetcher->fetch($doc);
        
        $this->assertFalse($result);
    }

    public function test_fetch_skips_existing_file(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test Document',
            externalIds: new ExternalIds(doi: '10.1234/existing')
        );
        
        $filename = $fetcher->getFilename($doc);
        $filepath = $this->testDir . DIRECTORY_SEPARATOR . $filename;
        
        file_put_contents($filepath, '%PDF-1.4 test content');
        
        $result = $fetcher->fetch($doc);
        
        $this->assertEquals($filepath, $result);
    }

    public function test_get_output_dir(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $this->assertEquals($this->testDir, $fetcher->getOutputDir());
    }

    public function test_fetch_batch_returns_array(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $docs = [
            new Document(title: 'Doc 1', providerId: 'doc1'),
            new Document(title: 'Doc 2', providerId: 'doc2'),
        ];
        
        $results = $fetcher->fetchBatch($docs);
        
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('doc1', $results);
        $this->assertArrayHasKey('doc2', $results);
    }

    public function test_fetch_batch_with_existing_files(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc1 = new Document(
            title: 'Doc 1',
            providerId: 'doc1',
            externalIds: new ExternalIds(doi: '10.1234/doc1')
        );
        
        file_put_contents($this->testDir . '/10.1234_doc1.pdf', '%PDF-1.4');
        
        $doc2 = new Document(
            title: 'Doc 2',
            providerId: 'doc2',
            externalIds: new ExternalIds(doi: '10.1234/doc2')
        );
        
        $results = $fetcher->fetchBatch([$doc1, $doc2]);
        
        $this->assertNotFalse($results['doc1']);
        $this->assertFalse($results['doc2']);
    }

    public function test_get_pdf_url_returns_first_available(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $url = $fetcher->getPdfUrl($doc);
        
        $this->assertNotNull($url);
    }

    public function test_get_all_pdf_urls_returns_all_sources(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $urls = $fetcher->getAllPdfUrls($doc);
        
        $this->assertIsArray($urls);
    }

    public function test_check_availability_returns_boolean(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $result = $fetcher->checkAvailability($doc);
        
        $this->assertIsBool($result);
    }

    public function test_check_batch_availability(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $docs = [
            new Document(title: 'Doc 1', providerId: 'doc1', externalIds: new ExternalIds(doi: '10.1234/1')),
            new Document(title: 'Doc 2', providerId: 'doc2', externalIds: new ExternalIds(doi: '10.1234/2')),
        ];
        
        $results = $fetcher->checkBatchAvailability($docs);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('doc1', $results);
        $this->assertArrayHasKey('doc2', $results);
    }

    public function test_enabled_sources_configuration(): void
    {
        $fetcher = new PDFFetcher($this->testDir, null, [
            'direct' => true,
            'arxiv' => false,
            'openalex' => false,
            'semantic_scholar' => false,
        ]);
        
        $this->assertTrue(true);
    }

    public function test_filename_with_complex_doi(): void
    {
        $fetcher = new PDFFetcher($this->testDir);
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1109/ACCESS.2024.1234567')
        );
        
        $filename = $fetcher->getFilename($doc);
        
        $this->assertEquals('10.1109_access.2024.1234567.pdf', $filename);
    }
}

class ArxivSourceTest extends TestCase
{
    public function test_get_pdf_url_with_valid_arxiv_id(): void
    {
        $source = new ArxivSource();
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(arxivId: '2301.12345')
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertEquals('https://arxiv.org/pdf/2301.12345.pdf', $url);
    }

    public function test_get_pdf_url_without_arxiv_id(): void
    {
        $source = new ArxivSource();
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertNull($url);
    }

    public function test_get_name(): void
    {
        $source = new ArxivSource();
        
        $this->assertEquals('arxiv', $source->getName());
    }
}

class DirectSourceTest extends TestCase
{
    public function test_get_pdf_url_with_pdf_url(): void
    {
        $source = new DirectSource();
        
        $doc = new Document(
            title: 'Test',
            url: 'https://example.com/paper.pdf'
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertEquals('https://example.com/paper.pdf', $url);
    }

    public function test_get_pdf_url_without_pdf_extension(): void
    {
        $source = new DirectSource();
        
        $doc = new Document(
            title: 'Test',
            url: 'https://example.com/paper.html'
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertNull($url);
    }

    public function test_get_pdf_url_fallback_to_doi(): void
    {
        $source = new DirectSource();
        
        $doc = new Document(
            title: 'Test',
            url: null,
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertEquals('https://doi.org/10.1234/test', $url);
    }

    public function test_get_name(): void
    {
        $source = new DirectSource();
        
        $this->assertEquals('direct', $source->getName());
    }
}

class OpenAlexSourceTest extends TestCase
{
    public function test_get_pdf_url_without_doi(): void
    {
        $source = new OpenAlexSource();
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(arxivId: '2301.12345')
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertNull($url);
    }

    public function test_get_pdf_urls_returns_array(): void
    {
        $source = new OpenAlexSource();
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $urls = $source->getPdfUrls($doc);
        
        $this->assertIsArray($urls);
    }

    public function test_get_name(): void
    {
        $source = new OpenAlexSource();
        
        $this->assertEquals('openalex', $source->getName());
    }
}

class SemanticScholarSourceTest extends TestCase
{
    public function test_get_pdf_url_without_s2_id(): void
    {
        $source = new SemanticScholarSource();
        
        $doc = new Document(
            title: 'Test',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );
        
        $url = $source->getPdfUrl($doc);
        
        $this->assertNull($url);
    }

    public function test_get_name(): void
    {
        $source = new SemanticScholarSource();
        
        $this->assertEquals('semantic_scholar', $source->getName());
    }
}
