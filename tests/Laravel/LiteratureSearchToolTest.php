<?php

namespace Nexus\Tests\Laravel;

use Nexus\Laravel\Tools\LiteratureSearchTool;
use Nexus\Models\Document;
use Nexus\Models\Query;
use PHPUnit\Framework\TestCase;

class LiteratureSearchToolTest extends TestCase
{
    public function test_tool_has_description(): void
    {
        $tool = new LiteratureSearchTool();

        $description = $tool->description();

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('literature', strtolower($description));
    }

    public function test_tool_can_set_custom_description(): void
    {
        $tool = new LiteratureSearchTool();
        $customDescription = 'Search for papers about machine learning';

        $result = $tool->withDescription($customDescription);

        $this->assertSame($tool, $result);
        $this->assertEquals($customDescription, $tool->description());
    }

    public function test_tool_can_set_providers(): void
    {
        $tool = new LiteratureSearchTool();
        $providers = ['openalex', 'crossref'];

        $result = $tool->withProviders($providers);

        $this->assertSame($tool, $result);
    }

    public function test_tool_can_set_abstract_flag(): void
    {
        $tool = new LiteratureSearchTool();

        $tool->withAbstract(false);
        $this->assertSame($tool, $tool->withAbstract(true));
    }

    public function test_tool_can_set_authors_flag(): void
    {
        $tool = new LiteratureSearchTool();

        $tool->withAuthors(false);
        $this->assertSame($tool, $tool->withAuthors(true));
    }

    public function test_tool_can_be_created_via_factory(): void
    {
        $tool = LiteratureSearchTool::make();

        $this->assertInstanceOf(LiteratureSearchTool::class, $tool);
    }

    public function test_tool_has_schema_definition(): void
    {
        $tool = new LiteratureSearchTool();

        $this->assertTrue(method_exists($tool, 'schema'));
    }

    public function test_tool_has_handle_method(): void
    {
        $tool = new LiteratureSearchTool();

        $this->assertTrue(method_exists($tool, 'handle'));
    }

    public function test_tool_accepts_custom_searcher(): void
    {
        $customResults = [
            new Document(
                title: 'Test Paper',
                year: 2024,
                provider: 'test',
                providerId: '123'
            )
        ];

        $tool = LiteratureSearchTool::make(function ($query, $providers) use ($customResults) {
            $this->assertInstanceOf(Query::class, $query);
            return $customResults;
        });

        $this->assertInstanceOf(LiteratureSearchTool::class, $tool);
    }
}
