<?php

namespace Nexus\Tests;

use Nexus\Models\Author;
use Nexus\Models\Document;
use Nexus\Models\ExternalIds;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function test_can_create_document()
    {
        $doc = new Document(
            title: 'Test Paper',
            year: 2024,
            provider: 'openalex',
            providerId: 'W1234567'
        );

        $this->assertEquals('Test Paper', $doc->title);
        $this->assertEquals(2024, $doc->year);
        $this->assertEquals('openalex', $doc->provider);
        $this->assertEquals('W1234567', $doc->providerId);
    }

    public function test_document_defaults()
    {
        $doc = new Document(title: 'Test');

        $this->assertEquals('unknown', $doc->provider);
        $this->assertEquals('', $doc->providerId);
        $this->assertInstanceOf(ExternalIds::class, $doc->externalIds);
        $this->assertInstanceOf(\DateTime::class, $doc->retrievedAt);
    }

    public function test_document_with_authors()
    {
        $authors = [
            new Author('Smith', 'John', '0000-0001-2345-6789'),
            new Author('Doe', 'Jane'),
        ];

        $doc = new Document(
            title: 'Collaborative Research',
            authors: $authors
        );

        $this->assertCount(2, $doc->authors);
        $this->assertEquals('John Smith', $doc->authors[0]->getFullName());
    }

    public function test_document_to_array()
    {
        $doc = new Document(
            title: 'Test Paper',
            year: 2024,
            provider: 'openalex',
            providerId: 'W1234567',
            externalIds: new ExternalIds(doi: '10.1234/test')
        );

        $array = $doc->toArray();

        $this->assertEquals('Test Paper', $array['title']);
        $this->assertEquals(2024, $array['year']);
        $this->assertEquals('openalex', $array['provider']);
        $this->assertEquals('10.1234/test', $array['external_ids']['doi']);
    }
}
