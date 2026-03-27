<?php

namespace Nexus\Tests;

use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class ExternalIdsTest extends TestCase
{
    public function test_can_create_external_ids()
    {
        $ids = new ExternalIds(
            doi: '10.1234/test',
            arxivId: '2301.12345',
            pubmedId: '12345678',
            openalexId: 'W1234567',
            s2Id: 'paper-123'
        );

        $this->assertEquals('10.1234/test', $ids->doi);
        $this->assertEquals('2301.12345', $ids->arxivId);
        $this->assertEquals('12345678', $ids->pubmedId);
        $this->assertEquals('W1234567', $ids->openalexId);
        $this->assertEquals('paper-123', $ids->s2Id);
    }

    public function test_doi_normalization()
    {
        $ids1 = new ExternalIds(doi: 'https://doi.org/10.1234/test');
        $this->assertEquals('10.1234/test', $ids1->doi);

        $ids2 = new ExternalIds(doi: 'http://dx.doi.org/10.1234/test');
        $this->assertEquals('10.1234/test', $ids2->doi);

        $ids3 = new ExternalIds(doi: 'doi: 10.1234/TEST');
        $this->assertEquals('10.1234/test', $ids3->doi);
    }

    public function test_external_ids_to_array()
    {
        $ids = new ExternalIds(doi: '10.1234/test', arxivId: '2301.12345');

        $array = $ids->toArray();

        $this->assertEquals('10.1234/test', $array['doi']);
        $this->assertEquals('2301.12345', $array['arxiv_id']);
        $this->assertNull($array['pubmed_id']);
        $this->assertNull($array['openalex_id']);
        $this->assertNull($array['s2_id']);
    }
}
