<?php

namespace App\Support\Integrations;

use App\Contracts\TextGenerator;
use ImranDevBd\AiHub\Facades\AIHub;

class AiHubAdapter implements TextGenerator
{
    /**
     * @return array{content:string,tokens:int,cost_minor:int}
     */
    public function generate(string $prompt, string $provider, ?string $model = null): array
    {
        $request = AIHub::provider($provider)->prompt($prompt)->maxTokens(1000);
        if ($model) {
            $request->model($model);
        }
        $response = $request->send();
        if (! $response->success) {
            throw new \RuntimeException('AI provider could not complete this request.');
        }

        return ['content' => $response->content, 'tokens' => $response->totalTokens, 'cost_minor' => (int) ceil($response->costUsd * 100)];
    }
}
