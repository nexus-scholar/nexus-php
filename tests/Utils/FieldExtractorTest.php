<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\FieldExtractor;
use PHPUnit\Framework\TestCase;

class FieldExtractorTest extends TestCase
{
    private array $data = [
        'name' => 'Test Paper',
        'year' => 2024,
        'authors' => [
            ['name' => 'John', 'last' => 'Smith'],
            ['name' => 'Jane', 'last' => 'Doe'],
        ],
        'metadata' => [
            'doi' => '10.1234/test',
            'nested' => [
                'value' => 'deep',
            ],
        ],
        'nullable' => null,
    ];

    public function test_get_simple_value()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('Test Paper', $extractor->get('name'));
        $this->assertEquals(2024, $extractor->get('year'));
    }

    public function test_get_nested_value()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('10.1234/test', $extractor->get('metadata.doi'));
        $this->assertEquals('deep', $extractor->get('metadata.nested.value'));
    }

    public function test_get_array_index()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('John', $extractor->get('authors.0.name'));
        $this->assertEquals('Smith', $extractor->get('authors.0.last'));
    }

    public function test_get_default_value()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('default', $extractor->get('nonexistent', 'default'));
        $this->assertEquals('default', $extractor->get('metadata.missing', 'default'));
    }

    public function test_get_null_returns_default()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('default', $extractor->get('nullable', 'default'));
    }

    public function test_get_string()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('Test Paper', $extractor->getString('name'));
        $this->assertEquals('default', $extractor->getString('nonexistent', 'default'));
    }

    public function test_get_int()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals(2024, $extractor->getInt('year'));
        $this->assertEquals(42, $extractor->getInt('nonexistent', 42));
        $this->assertNull($extractor->getInt('nonexistent'));
    }

    public function test_get_list()
    {
        $extractor = new FieldExtractor($this->data);

        $authors = $extractor->getList('authors');
        $this->assertCount(2, $authors);
    }

    public function test_get_first_found()
    {
        $extractor = new FieldExtractor($this->data);

        $this->assertEquals('deep', $extractor->getFirst('metadata.nested.value', 'missing'));
        $this->assertNull($extractor->getFirst('missing', 'also.missing'));
    }
}
