<?php

namespace App\Contracts;

interface TextGenerator
{
    /**
     * @return array{content:string,tokens:int,cost_minor:int}
     */
    public function generate(string $prompt, string $provider, ?string $model = null): array;
}
