<?php

use App\Jobs\PrepareCampaign;
use App\Jobs\ProcessFeedback;
use App\Models\AutomationRun;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\DataExport;
use App\Models\FeedbackEvent;
use App\Models\Module;
use App\Services\AutomationEngine;
use App\Services\ModuleCatalog;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('app:install {--demo}', function () {
    if (! config('app.key')) {
        $this->call('key:generate');
    }
    if (config('database.default') === 'sqlite' && ! file_exists(database_path('database.sqlite'))) {
        touch(database_path('database.sqlite'));
    }
    $this->call('migrate', ['--force' => true]);
    $this->call('filament:assets');
    app(ModuleCatalog::class)->sync();
    if ($this->option('demo')) {
        $this->call('db:seed', ['--force' => true]);
    }
    $this->info('Mailora is ready. Run php artisan serve, queue:work, and schedule:work.');
});
Artisan::command('demo:seed', function () {
    return $this->call('db:seed');
});
Artisan::command('demo:reset {--force}', function () {
    if (! config('platform.demo') || app()->isProduction()) {
        $this->error('Demo reset requires APP_DEMO=true outside production.');

        return 1;
    }
    if (! $this->option('force') && ! $this->confirm('Delete all local data and rebuild the demo?')) {
        return 1;
    }

    return $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
});
Artisan::command('modules:list', function () {
    $this->table(['Key', 'Name'], Module::all(['key', 'name'])->toArray());
});
foreach (['enable' => true, 'disable' => false] as $action => $enabled) {
    Artisan::command('modules:'.$action.' {business} {key}', function () use ($enabled) {
        app(TenantContext::class)->run((int) $this->argument('business'), null, fn () => app(ModuleRegistry::class)->toggle($this->argument('key'), $enabled));
        $this->info('Module configuration updated.');
    });
}
Artisan::command('automations:tick', function () {
    foreach (Business::cursor() as $business) {
        app(TenantContext::class)->run($business->id, null, function () {
            AutomationRun::where('status', 'running')->where('next_run_at', '<=', now())->chunkById(100, function ($runs) {
                foreach ($runs as $run) {
                    app(TenantContext::class)->run($run->business_id, $run->branch_id, fn () => app(AutomationEngine::class)->tick($run->id));
                }
            });
        });
    }
});
Artisan::command('send:resume {business}', function () {
    app(TenantContext::class)->run((int) $this->argument('business'), null, function () {
        foreach (Campaign::whereIn('status', ['sending', 'preparing'])->cursor() as $campaign) {
            PrepareCampaign::dispatch($campaign->business_id, $campaign->branch_id, $campaign->id);
        }
    });
    $this->info('Active campaigns requeued; sent message keys are preserved.');
});
Artisan::command('demo:simulate-engagement', function () {
    if (! config('platform.demo') || app()->isProduction()) {
        $this->error('Available only in local demo mode.');

        return 1;
    }
    foreach (Business::cursor() as $business) {
        app(TenantContext::class)->run($business->id, null, function () {
            Campaign::where('status', 'sent')->chunkById(100, function ($campaigns) {
                foreach ($campaigns as $c) {
                    $c->update(['open_count' => min($c->sent_count, $c->open_count + random_int(1, 30)), 'click_count' => min($c->open_count, $c->click_count + random_int(0, 5))]);
                }
            });
        });
    }
    $this->info('Seeded campaign metrics updated.');
});
Artisan::command('health:check', function () {
    DB::select('select 1');
    $this->info('Database connection: healthy');
    $this->info('Delivery mode: '.(config('platform.sandbox') ? 'sandbox' : 'blocked'));
});
Schedule::command('automations:tick')->everyMinute()->withoutOverlapping();

Artisan::command('exports:prune', function () {
    $count = 0;
    foreach (Business::cursor() as $business) {
        app(TenantContext::class)->run($business->id, null, function () use (&$count) {
            foreach (DataExport::where('status', 'ready')->where('expires_at', '<', now())->cursor() as $export) {
                Storage::disk('local')->delete($export->path);
                $export->update(['status' => 'expired', 'path' => null]);
                $count++;
            }
        });
    }$this->info("Expired exports removed: $count");
});
Schedule::command('exports:prune')->hourly()->withoutOverlapping();

Artisan::command('modules:sync', function () {
    app(ModuleCatalog::class)->sync();
    $this->info('Module catalog synchronized; workspace toggle choices preserved.');
});

Artisan::command('feedback:resume {business}', function () {
    app(TenantContext::class)->run((int) $this->argument('business'), null, function () {
        if (! app(ModuleRegistry::class)->enabled('feedback')) {
            return;
        }
        foreach (FeedbackEvent::whereIn('status', ['pending', 'failed'])->cursor() as $event) {
            ProcessFeedback::dispatch($event->business_id, $event->branch_id, $event->id);
        }
    });
    $this->info('Eligible feedback receipts queued.');
});
