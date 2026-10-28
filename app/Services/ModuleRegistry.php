<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleRegistry
{
    /** @var Collection<int,Module>|null */
    private $definitions = null;

    /** @phpstan-impure Reads mutable workspace flags from the database. */
    public function enabled(string $key): bool
    {
        $context = app(TenantContext::class);
        if (! $context->businessId) {
            return false;
        }
        $this->definitions ??= Module::all();
        $module = $this->definitions->firstWhere('key', $key);
        if (! $module) {
            return false;
        }
        if ($module->is_core) {
            return true;
        }
        $business = DB::table('business_modules')->where('business_id', $context->businessId)->where('module_key', $key)->value('enabled');
        if (! ($business ?? $module->default_enabled)) {
            return false;
        }
        if ($context->branchId && DB::table('branch_modules')->where('branch_id', $context->branchId)->where('module_key', $key)->value('enabled') === 0) {
            return false;
        }
        foreach ($module->depends_on as $dependency) {
            if (! $this->enabled($dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $seen
     */
    public function toggle(string $key, bool $enabled, array $seen = []): void
    {
        $module = Module::where('key', $key)->firstOrFail();
        if (in_array($key, $seen, true)) {
            throw ValidationException::withMessages(['module' => 'Circular module dependency.']);
        }
        if ($module->is_core && ! $enabled) {
            throw ValidationException::withMessages(['module' => 'Core modules cannot be disabled.']);
        }
        DB::transaction(function () use ($module, $key, $enabled, $seen) {
            if ($enabled) {
                foreach ($module->depends_on as $dependency) {
                    $this->toggle($dependency, true, [...$seen, $key]);
                }
            } else {
                foreach (Module::all() as $dependent) {
                    if (in_array($key, $dependent->depends_on, true) && $this->enabled($dependent->key)) {
                        throw ValidationException::withMessages(['module' => "Disable {$dependent->name} first."]);
                    }
                }
            }
            DB::table('business_modules')->updateOrInsert(['business_id' => app(TenantContext::class)->businessId, 'module_key' => $key], ['enabled' => $enabled, 'enabled_at' => now(), 'enabled_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]);
            Audit::record('module.'.($enabled ? 'enabled' : 'disabled'), $module);
        });
    }
}
