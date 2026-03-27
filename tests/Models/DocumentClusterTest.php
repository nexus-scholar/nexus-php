<?php

namespace Nexus\Tests;

use Nexus\Models\DocumentCluster;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class DocumentClusterTest extends TestCase
{
    private function createDocument(string $title, string $provider, ?int $year = null): Document
    {
        return new Document(
            title: $title,
            year: $year,
            provider: $provider,
            providerId: uniqid(),
            externalIds: new ExternalIds()
        );
    }

    public function test_can_create_cluster()
    {
        $docs = [
            $this->createDocument('Paper 1', 'openalex', 2024),
            $this->createDocument('Paper 2', 'crossref', 2024),
        ];

        $cluster = new DocumentCluster(
            clusterId: 0,
            representative: $docs[0],
            members: $docs,
            allDois: ['10.1234/test'],
            allArxivIds: ['2301.12345'],
            providerCounts: ['openalex' => 1, 'crossref' => 1]
        );

        $this->assertEquals(0, $cluster->clusterId);
        $this->assertEquals('Paper 1', $cluster->representative->title);
        $this->assertCount(2, $cluster->members);
        $this->assertEquals(2, $cluster->size());
    }

    public function test_cluster_size()
    {
        $docs = [
            $this->createDocument('Paper 1', 'openalex'),
            $this->createDocument('Paper 2', 'crossref'),
            $this->createDocument('Paper 3', 'arxiv'),
        ];

        $cluster = new DocumentCluster(
            clusterId: 0,
            representative: $docs[0],
            members: $docs
        );

        $this->assertEquals(3, $cluster->size());
    }

    public function test_cluster_to_array()
    {
        $docs = [
            $this->createDocument('Paper 1', 'openalex', 2024),
        ];

        $cluster = new DocumentCluster(
            clusterId: 0,
            representative: $docs[0],
            members: $docs,
            allDois: ['10.1234/test'],
            providerCounts: ['openalex' => 1]
        );

        $array = $cluster->toArray();

        $this->assertEquals(0, $array['cluster_id']);
        $this->assertEquals('Paper 1', $array['representative']['title']);
        $this->assertCount(1, $array['members']);
        $this->assertEquals(['10.1234/test'], $array['all_dois']);
        $this->assertEquals(1, $array['size']);
    }
}
