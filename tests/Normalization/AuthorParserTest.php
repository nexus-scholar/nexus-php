<?php

namespace Nexus\Tests\Normalization;

use Nexus\Normalization\AuthorParser;
use Nexus\Models\Author;
use PHPUnit\Framework\TestCase;

class AuthorParserTest extends TestCase
{
    public function testParseAuthorNameLastFirst(): void
    {
        $result = AuthorParser::parseAuthorName('Smith, John');

        $this->assertEquals('Smith', $result['family']);
        $this->assertEquals('John', $result['given']);
    }

    public function testParseAuthorNameFirstLast(): void
    {
        $result = AuthorParser::parseAuthorName('John Smith');

        $this->assertEquals('Smith', $result['family']);
        $this->assertEquals('John', $result['given']);
    }

    public function testParseAuthorNameSingleName(): void
    {
        $result = AuthorParser::parseAuthorName('Smith');

        $this->assertEquals('Smith', $result['family']);
        $this->assertNull($result['given']);
    }

    public function testParseAuthorNameEmpty(): void
    {
        $result = AuthorParser::parseAuthorName('');

        $this->assertEquals('Unknown', $result['family']);
        $this->assertNull($result['given']);
    }

    public function testParseAuthorsFromStrings(): void
    {
        $data = ['John Smith', 'Jane Doe'];

        $authors = AuthorParser::parseAuthors($data);

        $this->assertCount(2, $authors);
        $this->assertInstanceOf(Author::class, $authors[0]);
        $this->assertEquals('Smith', $authors[0]->familyName);
        $this->assertEquals('Jane', $authors[1]->givenName);
    }

    public function testParseAuthorsFromDicts(): void
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

    public function testParseAuthorsWithMissingOrcid(): void
    {
        $data = [
            ['name' => 'John Smith', 'ORCID' => '0000-0000-0000-0002'],
        ];

        $authors = AuthorParser::parseAuthors($data);

        $this->assertEquals('0000-0000-0000-0002', $authors[0]->orcid);
    }
}
