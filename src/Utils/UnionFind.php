<?php

namespace Nexus\Utils;

class UnionFind
{
    private array $parent;

    private array $rank;

    public function __construct(int $n)
    {
        $this->parent = range(0, $n - 1);
        $this->rank = array_fill(0, $n, 0);
    }

    public function find(int $i): int
    {
        if ($this->parent[$i] === $i) {
            return $i;
        }
        $this->parent[$i] = $this->find($this->parent[$i]);

        return $this->parent[$i];
    }

    public function union(int $i, int $j): void
    {
        $rootI = $this->find($i);
        $rootJ = $this->find($j);

        if ($rootI !== $rootJ) {
            if ($this->rank[$rootI] < $this->rank[$rootJ]) {
                $this->parent[$rootI] = $rootJ;
            } elseif ($rootI > $this->rank[$rootJ]) {
                $this->parent[$rootJ] = $rootI;
            } else {
                $this->parent[$rootI] = $rootJ;
                $this->rank[$rootJ]++;
            }
        }
    }
}
