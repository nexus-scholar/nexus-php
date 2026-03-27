<?php

namespace Nexus\Tests\Normalization;

use Nexus\Normalization\IDExtractor;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class IDExtractorTest extends TestCase
{
    public function testExtractDoi(): void
    {
        $extractor = new IDExtractor(['doi' => '10.1234/example']);

        $this->assertEquals('10.1234/example', $extractor->extractDoi());
    }

    public function testExtractDoiWithPrefix(): void
    {
        $extractor = new IDExtractor(['doi' => 'https://doi.org/10.1234/example']);

        $doi = $extractor->extractDoi();
        $this->assertNotNull($doi);
    }

    public function testExtractArxivId(): void
    {
        $extractor = new IDExtractor(['arxiv_id' => '2301.12345']);

        $this->assertEquals('2301.12345', $extractor->extractArxivId());
    }

    public function testExtractArxivIdWithPrefix(): void
    {
        $extractor = new IDExtractor(['arxiv_id' => 'arXiv:2301.12345']);

        $this->assertEquals('2301.12345', $extractor->extractArxivId());
    }

    public function testExtractPmid(): void
    {
        $extractor = new IDExtractor(['pmid' => '12345678']);

        $this->assertEquals('12345678', $extractor->extractPmid());
    }

    public function testExtractOpenalexId(): void
    {
        $extractor = new IDExtractor(['id' => 'W1234567890']);

        $this->assertEquals('W1234567890', $extractor->extractOpenalexId());
    }

    public function testExtractOpenalexIdFromUrl(): void
    {
        $extractor = new IDExtractor(['id' => 'https://openalex.org/W1234567890']);

        $this->assertEquals('W1234567890', $extractor->extractOpenalexId());
    }

    public function testExtractS2Id(): void
    {
        $extractor = new IDExtractor(['corpusId' => '12345678']);

        $this->assertEquals('12345678', $extractor->extractS2Id());
    }

    public function testExtractAll(): void
    {
        $data = [
            'doi' => '10.1234/test',
            'arxiv_id' => '2301.12345',
            'pmid' => '12345678',
            'id' => 'W1234567890',
            'corpusId' => '99999999',
        ];

        $extractor = new IDExtractor($data);
        $ids = $extractor->extractAll();

        $this->assertInstanceOf(ExternalIds::class, $ids);
        $this->assertEquals('10.1234/test', $ids->doi);
        $this->assertEquals('2301.12345', $ids->arxivId);
        $this->assertEquals('12345678', $ids->pubmedId);
        $this->assertEquals('W1234567890', $ids->openalexId);
        $this->assertEquals('99999999', $ids->s2Id);
    }

    public function testGetFirst(): void
    {
        $extractor = new IDExtractor(['foo' => null, 'bar' => 'value']);

        $this->assertEquals('value', $extractor->getFirst('foo', 'bar'));
        $this->assertNull($extractor->getFirst('nonexistent'));
    }

    public function testNestedGet(): void
    {
        $extractor = new IDExtractor(['outer' => ['inner' => 'value']]);

        $this->assertEquals('value', $extractor->get('outer.inner'));
    }
}
