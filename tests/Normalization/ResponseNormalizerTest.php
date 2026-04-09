<?php

namespace Nexus\Tests\Normalization;

use Nexus\Models\Document;
use Nexus\Normalization\AuthorParser;
use Nexus\Normalization\ResponseNormalizer;
use PHPUnit\Framework\TestCase;

class ResponseNormalizerTest extends TestCase
{
    public function test_normalize_basic_document(): void
    {
        $normalizer = new ResponseNormalizer('openalex');

        $data = [
            'title' => 'Test Paper',
            'publication_year' => 2023,
            'authorships' => [
                ['author' => ['display_name' => 'John Smith']],
            ],
            'doi' => '10.1234/test',
            'abstract' => 'This is a test abstract',
            'publicationvenue' => ['display_name' => 'Test Journal'],
        ];

        $fieldMap = [
            'title' => 'title',
            'year' => 'publication_year',
            'authors' => 'authorships',
            'abstract' => 'abstract',
            'venue' => 'publicationvenue.display_name',
        ];

        $doc = $normalizer->normalize($data, $fieldMap);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertEquals('Test Paper', $doc->title);
        $this->assertEquals(2023, $doc->year);
        $this->assertEquals('openalex', $doc->provider);
        $this->assertEquals('10.1234/test', $doc->externalIds->doi);
        $this->assertCount(1, $doc->authors);
        $this->assertEquals('Test Journal', $doc->venue);
    }

    public function test_normalize_with_missing_title(): void
    {
        $normalizer = new ResponseNormalizer('crossref');

        $data = [
            'doi' => '10.1234/test',
        ];

        $doc = $normalizer->normalize($data, []);

        $this->assertNull($doc);
    }

    public function test_normalize_with_custom_author_parser(): void
    {
        $normalizer = new ResponseNormalizer('arxiv');

        $data = [
            'title' => 'Test Paper',
            'authors' => ['Smith, John', 'Doe, Jane'],
        ];

        $customParser = function ($data) {
            return AuthorParser::parseAuthors(
                $data['authors'],
                'name'
            );
        };

        $doc = $normalizer->normalize($data, ['title' => 'title', 'authors' => 'authors'], $customParser);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertCount(2, $doc->authors);
        $this->assertEquals('Smith', $doc->authors[0]->familyName);
    }

    public function test_normalize_with_no_data(): void
    {
        $normalizer = new ResponseNormalizer('test');

        $doc = $normalizer->normalize([], []);

        $this->assertNull($doc);
    }
}
