<?php

namespace Nexus\Tests\Utils;

use Nexus\Models\QueryField;
use Nexus\Utils\QueryParser;
use Nexus\Utils\QueryToken;
use PHPUnit\Framework\TestCase;

class QueryParserTest extends TestCase
{
    private QueryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new QueryParser();
    }

    public function test_parse_simple_word()
    {
        $tokens = $this->parser->parse('machine learning');

        $this->assertCount(2, $tokens);
        $this->assertEquals('machine', $tokens[0]->value);
        $this->assertEquals('learning', $tokens[1]->value);
    }

    public function test_parse_phrase()
    {
        $tokens = $this->parser->parse('"machine learning"');

        $this->assertCount(1, $tokens);
        $this->assertEquals('machine learning', $tokens[0]->value);
        $this->assertTrue($tokens[0]->isPhrase);
    }

    public function test_parse_field_prefix()
    {
        $tokens = $this->parser->parse('title:"deep learning"');

        $this->assertCount(1, $tokens);
        $this->assertEquals('deep learning', $tokens[0]->value);
        $this->assertEquals(QueryField::TITLE, $tokens[0]->field);
        $this->assertTrue($tokens[0]->isPhrase);
    }

    public function test_parse_operators()
    {
        $tokens = $this->parser->parse('machine AND learning');

        $this->assertCount(3, $tokens);
        $this->assertEquals('machine', $tokens[0]->value);
        $this->assertTrue($tokens[1]->isOperator);
        $this->assertEquals('AND', $tokens[1]->value);
        $this->assertEquals('learning', $tokens[2]->value);
    }

    public function test_parse_parentheses()
    {
        $tokens = $this->parser->parse('(machine OR learning)');

        $this->assertCount(5, $tokens);
        $this->assertEquals('(', $tokens[0]->value);
        $this->assertTrue($tokens[0]->isOperator);
        $this->assertEquals(')', $tokens[4]->value);
    }

    public function test_parse_field_prefix_for_word()
    {
        $tokens = $this->parser->parse('author:smith year:2020');

        $this->assertCount(2, $tokens);
        $this->assertEquals('smith', $tokens[0]->value);
        $this->assertEquals(QueryField::AUTHOR, $tokens[0]->field);
        $this->assertEquals('2020', $tokens[1]->value);
        $this->assertEquals(QueryField::YEAR, $tokens[1]->field);
    }

    public function test_normalize_text()
    {
        $input = "Hello\u{201c}World\u{201d}";
        $result = $this->parser->normalizeText($input);

        $this->assertStringContainsString('"', $result);
    }

    public function test_normalize_hyphen()
    {
        $input = "test\u{2011}value";
        $result = $this->parser->normalizeText($input);

        $this->assertStringContainsString('-', $result);
    }

    public function test_validate_valid_query()
    {
        $tokens = $this->parser->parse('machine AND learning');
        $this->assertTrue($this->parser->validate($tokens));
    }

    public function test_validate_balanced_parentheses()
    {
        $tokens = $this->parser->parse('(machine AND (learning OR AI))');
        $this->assertTrue($this->parser->validate($tokens));
    }

    public function test_validate_unbalanced_parentheses()
    {
        $tokens = $this->parser->parse('(machine AND learning');
        $this->assertFalse($this->parser->validate($tokens));
    }

    public function test_validate_extra_closing_paren()
    {
        $tokens = $this->parser->parse('machine AND learning)');
        $this->assertFalse($this->parser->validate($tokens));
    }

    public function test_validate_empty_query()
    {
        $tokens = [];
        $this->assertFalse($this->parser->validate($tokens));
    }

    public function test_parse_empty_string()
    {
        $tokens = $this->parser->parse('');
        $this->assertEmpty($tokens);
    }
}
