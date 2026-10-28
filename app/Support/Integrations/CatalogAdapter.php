<?php

namespace App\Support\Integrations;

use App\Contracts\CatalogEnricher;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use ImranDevBd\AiCatalog\Facades\AiCatalog;

class CatalogAdapter implements CatalogEnricher
{
    /**
     * @return array<string,mixed>
     */
    public function fromText(string $text): array
    {
        abort_unless(app(ModuleRegistry::class)->enabled('enrichment'), 403);
        $draft = AiCatalog::fromText($text)->tenant((string) app(TenantContext::class)->businessId)->locale(app()->getLocale())->generate();

        return $draft->payload ?? [];
    }
}
