<?php

namespace Nexus\Tests\Utils;

use Nexus\Utils\UnionFind;
use PHPUnit\Framework\TestCase;

class UnionFindTest extends TestCase
{
    public function test_initial_state()
    {
        $uf = new UnionFind(5);

        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i, $uf->find($i));
        }
    }

    public function test_union_same_set()
    {
        $uf = new UnionFind(3);

        $uf->union(0, 1);

        $this->assertEquals($uf->find(0), $uf->find(1));
        $this->assertNotEquals($uf->find(0), $uf->find(2));
    }

    public function test_union_transitive()
    {
        $uf = new UnionFind(4);

        $uf->union(0, 1);
        $uf->union(1, 2);

        $this->assertEquals($uf->find(0), $uf->find(1));
        $this->assertEquals($uf->find(1), $uf->find(2));
        $this->assertNotEquals($uf->find(0), $uf->find(3));
    }

    public function test_find_path_compression()
    {
        $uf = new UnionFind(3);

        $uf->union(0, 1);
        $uf->union(0, 2);

        $root = $uf->find(0);
        $this->assertEquals($root, $uf->find(1));
        $this->assertEquals($root, $uf->find(2));
    }

    public function test_union_same_elements_twice()
    {
        $uf = new UnionFind(2);

        $uf->union(0, 1);
        $root1 = $uf->find(0);
        
        $uf->union(0, 1);
        $root2 = $uf->find(0);

        $this->assertEquals($root1, $root2);
    }
}
