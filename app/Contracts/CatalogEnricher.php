<?php

namespace App\Contracts;

interface CatalogEnricher
{
    /**
     * @return array<string,mixed>
     */
    public function fromText(string $text): array;
}
