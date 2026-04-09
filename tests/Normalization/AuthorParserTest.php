<?php

namespace Nexus\Tests\Normalization;

use Nexus\Models\Author;
use Nexus\Normalization\AuthorParser;
use PHPUnit\Framework\TestCase;

class AuthorParserTest extends TestCase
{
    public function test_parse_author_name_last_first(): void
    {
        $result = AuthorParser::parseAuthorName('Smith, John');

        $this->assertEquals('Smith', $result['family']);
        $this->assertEquals('John', $result['given']);
    }

    public function test_parse_author_name_first_last(): void
    {
        $result = AuthorParser::parseAuthorName('John Smith');

        $this->assertEquals('Smith', $result['family']);
        $this->assertEquals('John', $result['given']);
    }

    public function test_parse_author_name_single_name(): void
    {
        $result = AuthorParser::parseAuthorName('Smith');

        $this->assertEquals('Smith', $result['family']);
        $this->assertNull($result['given']);
    }

    public function test_parse_author_name_empty(): void
    {
        $result = AuthorParser::parseAuthorName('');

        $this->assertEquals('Unknown', $result['family']);
        $this->assertNull($result['given']);
    }

    public function test_parse_authors_from_strings(): void
    {
        $data = ['John Smith', 'Jane Doe'];

        $authors = AuthorParser::parseAuthors($data);

        $this->assertCount(2, $authors);
        $this->assertInstanceOf(Author::class, $authors[0]);
        $this->assertEquals('Smith', $authors[0]->familyName);
        $this->assertEquals('Jane', $authors[1]->givenName);
    }

    public function test_parse_authors_from_dicts(): void
    {
        $data = [
            ['name' => 'John Smith', 'orcid' => '0000-0000-0000-0001'],
            ['name' => 'Jane Doe', 'orcid' => null],
        ];

        $authors = AuthorParser::parseAuthors($data);

        $this->assertCount(2, $authors);
        $this->assertEquals('0000-0000-0000-0001', $authors[0]->orcid);
        $this->assertEquals('Doe', $authors[1]->familyName);
    }

    public function test_parse_authors_with_missing_orcid(): void
    {
        $data = [
            ['name' => 'John Smith', 'ORCID' => '0000-0000-0000-0002'],
        ];

        $authors = AuthorParser::parseAuthors($data);

        $this->assertEquals('0000-0000-0000-0002', $authors[0]->orcid);
    }
}
