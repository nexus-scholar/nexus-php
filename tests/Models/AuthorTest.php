<?php

namespace Nexus\Tests;

use Nexus\Models\Author;
use PHPUnit\Framework\TestCase;

class AuthorTest extends TestCase
{
    public function test_author_with_full_name()
    {
        $author = new Author('Smith', 'John', '0000-0001-2345-6789');

        $this->assertEquals('Smith', $author->familyName);
        $this->assertEquals('John', $author->givenName);
        $this->assertEquals('0000-0001-2345-6789', $author->orcid);
        $this->assertEquals('John Smith', $author->getFullName());
    }

    public function test_author_without_given_name()
    {
        $author = new Author('Anonymous');

        $this->assertNull($author->givenName);
        $this->assertEquals('Anonymous', $author->getFullName());
    }

    public function test_author_to_array()
    {
        $author = new Author('Smith', 'John', '0000-0001-2345-6789');

        $array = $author->toArray();

        $this->assertEquals('Smith', $array['family_name']);
        $this->assertEquals('John', $array['given_name']);
        $this->assertEquals('0000-0001-2345-6789', $array['orcid']);
        $this->assertEquals('John Smith', $array['full_name']);
    }
}
