<?php

namespace Nexus\Tests\Dedup;

use Nexus\Dedup\ConservativeStrategy;
use Nexus\Models\DeduplicationConfig;
use Nexus\Models\DeduplicationStrategyName;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class ConservativeStrategyTest extends TestCase
{
    private function createDocument(
        string $title,
        ?string $doi = null,
        ?string $arxivId = null,
        ?int $year = null,
        ?int $citedByCount = null
    ): Document {
        return new Document(
            title: $title,
            year: $year,
            provider: 'test',
            providerId: uniqid(),
            externalIds: new ExternalIds(doi: $doi, arxivId: $arxivId),
            citedByCount: $citedByCount
        );
    }

    private function createStrategy(): ConservativeStrategy
    {
        $config = new DeduplicationConfig(
            strategy: DeduplicationStrategyName::CONSERVATIVE,
            fuzzyThreshold: 97,
            maxYearGap: 1
        );

        return new ConservativeStrategy($config);
    }

    public function test_empty_documents_returns_empty()
    {
        $strategy = $this->createStrategy();

        $result = $strategy->deduplicate([]);

        $this->assertEmpty($result);
    }

    public function test_single_document_returns_single_cluster()
    {
        $strategy = $this->createStrategy();
        $docs = [$this->createDocument('Single Paper', null, null, 2024)];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(1, $clusters);
        $this->assertEquals(1, $clusters[0]->size());
    }

    public function test_different_documents_no_doi_no_title_match()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Paper A', null, null, 2024),
            $this->createDocument('Paper B', null, null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(2, $clusters);
    }

    public function test_same_doi_creates_cluster()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Paper A', '10.1234/same', null, 2024),
            $this->createDocument('Paper B', '10.1234/same', null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(1, $clusters);
        $this->assertEquals(2, $clusters[0]->size());
    }

    public function test_same_arxiv_id_creates_cluster()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Paper A', null, '2301.00001', 2024),
            $this->createDocument('Paper B', null, '2301.00001', 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(1, $clusters);
        $this->assertEquals(2, $clusters[0]->size());
    }

    public function test_same_title_creates_cluster()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Exact Same Title', null, null, 2024),
            $this->createDocument('Exact Same Title', null, null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(1, $clusters);
        $this->assertEquals(2, $clusters[0]->size());
    }

    public function test_similar_titles_within_year_gap()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Deep Learning for Computer Vision', null, null, 2023),
            $this->createDocument('Deep Learning for Computer Vision Systems', null, null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(2, $clusters);
    }

    public function test_different_titles_no_cluster()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Completely Different Paper Alpha', null, null, 2024),
            $this->createDocument('Another Unrelated Paper Beta', null, null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(2, $clusters);
    }

    public function test_progress_callback()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Paper A', null, null, 2024),
            $this->createDocument('Paper B', null, null, 2024),
        ];

        $progress = [];
        $strategy->deduplicate($docs, function ($message, $percent) use (&$progress) {
            $progress[] = ['message' => $message, 'percent' => $percent];
        });

        $this->assertNotEmpty($progress);
    }

    public function test_cluster_has_correct_representative()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Lower Priority', '10.1234/test', null, 2024, 5),
            $this->createDocument('Higher Priority', '10.1234/test', null, 2024, 100),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertEquals('Higher Priority', $clusters[0]->representative->title);
    }

    public function test_multiple_clusters()
    {
        $strategy = $this->createStrategy();
        $docs = [
            $this->createDocument('Cluster A Paper 1', '10.1234/a', null, 2024),
            $this->createDocument('Cluster A Paper 2', '10.1234/a', null, 2024),
            $this->createDocument('Cluster B Paper 1', '10.1234/b', null, 2024),
        ];

        $clusters = $strategy->deduplicate($docs);

        $this->assertCount(2, $clusters);
        
        $sizes = array_map(fn($c) => $c->size(), $clusters);
        $this->assertContains(2, $sizes);
        $this->assertContains(1, $sizes);
    }
}
