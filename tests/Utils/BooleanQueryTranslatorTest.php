<?php

namespace Nexus\Tests\Utils;

use Nexus\Models\Query;
use Nexus\Models\QueryField;
use Nexus\Utils\BooleanQueryTranslator;
use PHPUnit\Framework\TestCase;

class BooleanQueryTranslatorTest extends TestCase
{
    public function test_translate_simple_query()
    {
        $translator = new BooleanQueryTranslator([]);
        $query = new Query(text: 'machine learning');

        $result = $translator->translate($query);

        $this->assertEquals('machine learning', $result['q']);
    }

    public function test_translate_with_field_mapping()
    {
        $fieldMap = [
            QueryField::TITLE->value => 'title',
            QueryField::AUTHOR->value => 'author',
        ];
        $translator = new BooleanQueryTranslator($fieldMap);
        $query = new Query(text: 'title:neural author:smith');

        $result = $translator->translate($query);

        $this->assertStringContainsString('title:neural', $result['q']);
        $this->assertStringContainsString('author:smith', $result['q']);
    }

    public function test_translate_phrase()
    {
        $translator = new BooleanQueryTranslator([]);
        $query = new Query(text: '"deep learning"');

        $result = $translator->translate($query);

        $this->assertStringContainsString('"deep learning"', $result['q']);
    }

    public function test_translate_operators()
    {
        $translator = new BooleanQueryTranslator([]);
        $query = new Query(text: 'machine AND learning OR AI');

        $result = $translator->translate($query);

        $this->assertStringContainsString('AND', $result['q']);
        $this->assertStringContainsString('OR', $result['q']);
    }

    public function test_translate_parentheses()
    {
        $translator = new BooleanQueryTranslator([]);
        $query = new Query(text: '(machine OR deep) AND learning');

        $result = $translator->translate($query);

        $this->assertStringContainsString('(', $result['q']);
        $this->assertStringContainsString(')', $result['q']);
    }

    public function test_translate_invalid_query_returns_original()
    {
        $translator = new BooleanQueryTranslator([]);
        $query = new Query(text: '(unbalanced');

        $result = $translator->translate($query);

        $this->assertEquals('(unbalanced', $result['q']);
    }

    public function test_escape_special_chars()
    {
        $translator = new BooleanQueryTranslator([], [], '+-');
        $query = new Query(text: 'test+value');

        $result = $translator->translate($query);

        $this->assertStringContainsString('\\+', $result['q']);
    }
}
