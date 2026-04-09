<?php

namespace Nexus\Tests\Dedup;

use Nexus\Dedup\DeduplicationStrategy;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class DeduplicationStrategyTest extends TestCase
{
    private function createDocument(string $title, ?string $doi = null, ?string $arxivId = null, ?int $year = null): Document
    {
        return new Document(
            title: $title,
            year: $year,
            provider: 'test',
            providerId: uniqid(),
            externalIds: new ExternalIds(doi: $doi, arxivId: $arxivId)
        );
    }

    public function test_normalize_title()
    {
        $result = DeduplicationStrategy::normalizeTitle('  The Quick Brown Fox  ');
        $this->assertEquals('the quick brown fox', $result);
    }

    public function test_normalize_title_removes_punctuation()
    {
        $result = DeduplicationStrategy::normalizeTitle("Test's, (Title) - With: Special; Chars!");
        $this->assertEquals('tests title with special chars', preg_replace('/\s+/', ' ', $result));
    }

    public function test_normalize_title_handles_empty()
    {
        $this->assertEquals('', DeduplicationStrategy::normalizeTitle(''));
        $this->assertEquals('', DeduplicationStrategy::normalizeTitle(null));
    }

    public function test_normalize_doi()
    {
        $result = DeduplicationStrategy::normalizeDoi('10.1234/test');
        $this->assertEquals('10.1234/test', $result);
    }

    public function test_normalize_doi_removes_url_prefix()
    {
        $this->assertEquals(
            '10.1234/test',
            DeduplicationStrategy::normalizeDoi('https://doi.org/10.1234/test')
        );
        $this->assertEquals(
            '10.1234/test',
            DeduplicationStrategy::normalizeDoi('http://dx.doi.org/10.1234/test')
        );
    }

    public function test_normalize_doi_removes_doi_prefix()
    {
        $this->assertEquals(
            '10.1234/test',
            DeduplicationStrategy::normalizeDoi('doi:10.1234/test')
        );
        $this->assertEquals(
            '10.1234/test',
            DeduplicationStrategy::normalizeDoi('DOI: 10.1234/TEST')
        );
    }

    public function test_normalize_doi_handles_empty()
    {
        $this->assertEquals('', DeduplicationStrategy::normalizeDoi(''));
        $this->assertEquals('', DeduplicationStrategy::normalizeDoi(null));
    }

    public function test_create_cluster_with_empty_documents_throws()
    {
        $this->expectException(\InvalidArgumentException::class);
        DeduplicationStrategy::createCluster(0, []);
    }

    public function test_create_cluster()
    {
        $docs = [
            $this->createDocument('Paper 1', '10.1234/one', '2301.00001', 2024),
            $this->createDocument('Paper 2', '10.1234/two', '2301.00002', 2024),
        ];

        $cluster = DeduplicationStrategy::createCluster(0, $docs);

        $this->assertEquals(0, $cluster->clusterId);
        $this->assertCount(2, $cluster->members);
        $this->assertCount(2, $cluster->allDois);
        $this->assertCount(2, $cluster->allArxivIds);
    }

    public function test_fuse_documents_uses_provider_priority()
    {
        $docs = [
            $this->createDocument('Low Priority', '10.1234/low'),
            $this->createDocument('High Priority', '10.1234/high'),
        ];
        $docs[0]->provider = 'arxiv';
        $docs[1]->provider = 'crossref';

        $fused = DeduplicationStrategy::fuseDocuments($docs);

        $this->assertEquals('High Priority', $fused->title);
        $this->assertEquals('crossref', $fused->provider);
    }

    public function test_fuse_documents_uses_citations_as_tiebreaker()
    {
        $docs = [
            $this->createDocument('Less Cited'),
            $this->createDocument('More Cited'),
        ];
        $docs[0]->citedByCount = 5;
        $docs[1]->citedByCount = 100;

        $fused = DeduplicationStrategy::fuseDocuments($docs);

        $this->assertEquals('More Cited', $fused->title);
        $this->assertEquals(100, $fused->citedByCount);
    }

    public function test_fuse_documents_merges_external_ids()
    {
        $doc1 = $this->createDocument('Doc 1');
        $doc1->externalIds = new ExternalIds(doi: '10.1234/one');

        $doc2 = $this->createDocument('Doc 2');
        $doc2->externalIds = new ExternalIds(arxivId: '2301.00001');

        $fused = DeduplicationStrategy::fuseDocuments([$doc1, $doc2]);

        $this->assertEquals('10.1234/one', $fused->externalIds->doi);
        $this->assertEquals('2301.00001', $fused->externalIds->arxivId);
    }

    public function test_fuse_documents_selects_longest_abstract()
    {
        $doc1 = $this->createDocument('Short Abstract');
        $doc1->abstract = 'Short';

        $doc2 = $this->createDocument('Long Abstract');
        $doc2->abstract = 'This is a much longer abstract that should be selected during fusion process.';

        $fused = DeduplicationStrategy::fuseDocuments([$doc1, $doc2]);

        $this->assertStringContainsString('longer abstract', $fused->abstract);
    }

    public function test_fuse_documents_takes_max_citations()
    {
        $doc1 = $this->createDocument('Doc 1');
        $doc1->citedByCount = 10;

        $doc2 = $this->createDocument('Doc 2');
        $doc2->citedByCount = 50;

        $fused = DeduplicationStrategy::fuseDocuments([$doc1, $doc2]);

        $this->assertEquals(50, $fused->citedByCount);
    }
}
