<?php

namespace Nexus\Tests\Laravel;

use Nexus\Laravel\Agents\LiteratureSearchAgent;
use Nexus\Laravel\Tools\LiteratureSearchTool;
use PHPUnit\Framework\TestCase;

class LiteratureSearchAgentTest extends TestCase
{
    public function test_agent_can_be_instantiated(): void
    {
        $agent = new LiteratureSearchAgent;

        $this->assertInstanceOf(LiteratureSearchAgent::class, $agent);
    }

    public function test_agent_has_default_instructions(): void
    {
        $agent = new LiteratureSearchAgent;

        $instructions = $agent->instructions();

        $this->assertIsString($instructions);
        $this->assertNotEmpty($instructions);
        $this->assertStringContainsString('literature', strtolower($instructions));
    }

    public function test_agent_can_set_custom_instructions(): void
    {
        $agent = new LiteratureSearchAgent;
        $customInstructions = 'You are a helpful assistant.';

        $result = $agent->withInstructions($customInstructions);

        $this->assertSame($agent, $result);
        $this->assertEquals($customInstructions, $agent->instructions());
    }

    public function test_agent_has_tools(): void
    {
        $agent = new LiteratureSearchAgent;

        $tools = $agent->tools();

        $this->assertIsIterable($tools);
        $this->assertNotEmpty($tools);

        $toolsArray = iterator_to_array($tools);
        $this->assertContainsOnlyInstancesOf(LiteratureSearchTool::class, $toolsArray);
    }

    public function test_agent_can_be_created_via_factory(): void
    {
        $agent = LiteratureSearchAgent::make();

        $this->assertInstanceOf(LiteratureSearchAgent::class, $agent);
    }

    public function test_agent_has_search_method(): void
    {
        $agent = new LiteratureSearchAgent;

        $this->assertTrue(method_exists($agent, 'search'));
    }

    public function test_agent_implements_agent_interface(): void
    {
        $agent = new LiteratureSearchAgent;

        $this->assertTrue(method_exists($agent, 'instructions'));
        $this->assertTrue(method_exists($agent, 'messages'));
        $this->assertTrue(method_exists($agent, 'tools'));
        $this->assertTrue(method_exists($agent, 'prompt'));
        $this->assertTrue(method_exists($agent, 'stream'));
        $this->assertTrue(method_exists($agent, 'queue'));
        $this->assertTrue(method_exists($agent, 'broadcast'));
        $this->assertTrue(method_exists($agent, 'broadcastNow'));
        $this->assertTrue(method_exists($agent, 'broadcastOnQueue'));
    }

    public function test_agent_with_provider(): void
    {
        $agent = new LiteratureSearchAgent;

        $result = $agent->withProvider('openalex');

        $this->assertSame($agent, $result);
    }

    public function test_agent_with_max_results(): void
    {
        $agent = new LiteratureSearchAgent;

        $result = $agent->withMaxResults(50);

        $this->assertSame($agent, $result);
    }

    public function test_agent_with_abstract(): void
    {
        $agent = new LiteratureSearchAgent;

        $result = $agent->withAbstract(true);

        $this->assertSame($agent, $result);
    }

    public function test_agent_with_authors(): void
    {
        $agent = new LiteratureSearchAgent;

        $result = $agent->withAuthors(true);

        $this->assertSame($agent, $result);
    }
}
