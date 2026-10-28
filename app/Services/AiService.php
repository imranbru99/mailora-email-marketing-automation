<?php

namespace App\Services;

use App\Contracts\TextGenerator;
use App\Models\AiGeneration;
use App\Models\Business;
use App\Models\UsageCounter;
use Illuminate\Support\Facades\DB;

class AiService
{
    public function execute(AiGeneration $generation): void
    {
        if (! app(ModuleRegistry::class)->enabled('ai-studio')) {
            return;
        }
        $settings = app(TenantContext::class)->settings();
        $claim = DB::transaction(function () use ($generation, $settings): ?array {
            Business::lockForUpdate()->findOrFail($generation->business_id);
            $current = AiGeneration::lockForUpdate()->findOrFail($generation->id);
            if ($current->status !== 'queued') {
                return null;
            }
            if (empty($settings['ai_enabled'])) {
                $current->update(['status' => 'disabled', 'result' => 'Connect an AI provider in deployment configuration and enable AI in business settings.']);

                return null;
            }
            if (mb_strlen($current->prompt) > 2000) {
                $current->update(['status' => 'failed', 'result' => 'Keep the brief within 2,000 characters.']);

                return null;
            }
            $prompt = 'Write marketing content. Do not invent factual claims. Request: '.$current->prompt;
            // Budget by UTF-8 bytes, with room for the adapter's 1,000-token output and overhead.
            $reserved = max(4000, strlen($prompt) + 2000);
            $period = now()->format('Y-m');
            $usage = UsageCounter::firstOrCreate(['business_id' => $current->business_id, 'period' => $period]);
            $limit = max(0, (int) ($settings['ai_token_limit'] ?? 10000));
            if ($usage->ai_tokens + $reserved > $limit) {
                $current->update(['status' => 'quota_exceeded', 'result' => 'The monthly AI token allowance has been reached.']);

                return null;
            }
            $usage->increment('ai_tokens', $reserved);
            $current->update(['status' => 'processing']);

            return ['prompt' => $prompt, 'period' => $period, 'reserved' => $reserved];
        });
        if (! $claim) {
            return;
        }
        try {
            $result = app(TextGenerator::class)->generate($claim['prompt'], $settings['ai_provider'] ?? 'openai', $settings['ai_model'] ?? null);
            DB::transaction(function () use ($generation, $result, $claim) {
                Business::lockForUpdate()->findOrFail($generation->business_id);
                $current = AiGeneration::lockForUpdate()->findOrFail($generation->id);
                if ($current->status !== 'processing') {
                    return;
                }
                $current->update(['status' => 'completed', 'result' => $result['content']]);
                $usage = UsageCounter::where('business_id', $generation->business_id)->where('period', $claim['period'])->firstOrFail();
                $usage->increment('ai_tokens', max(0, $result['tokens']) - $claim['reserved']);
                $usage->increment('ai_cost_minor', max(0, $result['cost_minor']));
            });
        } catch (\Throwable $e) {
            // An interrupted response may still be billable; keep its reservation for operator review.
            AiGeneration::whereKey($generation->id)->where('status', 'processing')->update(['status' => 'failed', 'result' => 'The provider could not complete this generation. Check provider configuration.']);
        }
    }
}
