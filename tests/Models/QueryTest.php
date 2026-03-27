<?php

namespace Nexus\Tests;

use Nexus\Models\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function test_can_create_query()
    {
        $query = new Query(text: 'machine learning');

        $this->assertEquals('machine learning', $query->text);
        $this->assertEquals('en', $query->language);
        $this->assertEquals(0, $query->offset);
        $this->assertIsString($query->id);
        $this->assertStringStartsWith('Q', $query->id);
    }

    public function test_query_with_custom_id()
    {
        $query = new Query(text: 'test', id: 'my-query-id');

        $this->assertEquals('my-query-id', $query->id);
    }

    public function test_query_with_year_range()
    {
        $query = new Query(
            text: 'deep learning',
            yearMin: 2020,
            yearMax: 2024
        );

        $this->assertEquals(2020, $query->yearMin);
        $this->assertEquals(2024, $query->yearMax);
    }

    public function test_query_to_array()
    {
        $query = new Query(
            text: 'test query',
            yearMin: 2020,
            yearMax: 2024,
            maxResults: 100,
            language: 'en'
        );

        $array = $query->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertEquals('test query', $array['text']);
        $this->assertEquals(2020, $array['year_min']);
        $this->assertEquals(2024, $array['year_max']);
        $this->assertEquals(100, $array['max_results']);
        $this->assertEquals('en', $array['language']);
    }

    public function test_query_with_metadata()
    {
        $query = new Query(
            text: 'test',
            metadata: ['source' => 'api', 'filter' => 'active']
        );

        $this->assertEquals('api', $query->metadata['source']);
        $this->assertEquals('active', $query->metadata['filter']);
    }
}
