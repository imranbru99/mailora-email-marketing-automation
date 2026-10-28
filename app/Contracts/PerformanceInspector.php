<?php

namespace App\Contracts;

interface PerformanceInspector
{
    /**
     * @return list<array<string,mixed>>
     */
    public function suggestions(): array;
}
