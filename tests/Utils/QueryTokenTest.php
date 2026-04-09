<?php

namespace Nexus\Tests\Utils;

use Nexus\Models\QueryField;
use Nexus\Utils\QueryToken;
use PHPUnit\Framework\TestCase;

class QueryTokenTest extends TestCase
{
    public function test_basic_token()
    {
        $token = new QueryToken('machine');

        $this->assertEquals('machine', $token->value);
        $this->assertEquals(QueryField::ANY, $token->field);
        $this->assertFalse($token->isPhrase);
        $this->assertFalse($token->isOperator);
    }

    public function test_phrase_token()
    {
        $token = new QueryToken('machine learning', QueryField::TITLE, isPhrase: true);

        $this->assertEquals('machine learning', $token->value);
        $this->assertEquals(QueryField::TITLE, $token->field);
        $this->assertTrue($token->isPhrase);
        $this->assertFalse($token->isOperator);
    }

    public function test_operator_token()
    {
        $token = new QueryToken('AND', isOperator: true);

        $this->assertEquals('AND', $token->value);
        $this->assertTrue($token->isOperator);
        $this->assertFalse($token->isPhrase);
    }

    public function test_token_to_string()
    {
        $token = new QueryToken('test', QueryField::TITLE, isPhrase: true);
        $str = (string) $token;

        $this->assertStringContainsString('QueryToken', $str);
        $this->assertStringContainsString('test', $str);
        $this->assertStringContainsString('title', $str);
    }
}
