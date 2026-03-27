<?php

namespace Nexus\Tests\Retrieval;

use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use Nexus\Retrieval\PDFFetcher;
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
}
