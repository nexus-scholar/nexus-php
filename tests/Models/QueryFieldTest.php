<?php

namespace Nexus\Tests\Models;

use Nexus\Models\QueryField;
use PHPUnit\Framework\TestCase;

class QueryFieldTest extends TestCase
{
    public function test_all_query_fields_exist()
    {
        $this->assertEquals('title', QueryField::TITLE->value);
        $this->assertEquals('abstract', QueryField::ABSTRACT->value);
        $this->assertEquals('full_text', QueryField::FULL_TEXT->value);
        $this->assertEquals('author', QueryField::AUTHOR->value);
        $this->assertEquals('year', QueryField::YEAR->value);
        $this->assertEquals('venue', QueryField::VENUE->value);
        $this->assertEquals('doi', QueryField::DOI->value);
        $this->assertEquals('keyword', QueryField::KEYWORD->value);
        $this->assertEquals('any', QueryField::ANY->value);
    }

    public function test_query_field_from_string()
    {
        $this->assertEquals(QueryField::TITLE, QueryField::tryFrom('title'));
        $this->assertNull(QueryField::tryFrom('unknown'));
        $this->assertNull(QueryField::tryFrom('invalid'));
    }
}
