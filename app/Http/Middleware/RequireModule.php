<?php

namespace App\Http\Middleware;

use App\Services\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;

class RequireModule
{
    public function handle(Request $request, Closure $next, string $module): mixed
    {
        abort_unless(app(ModuleRegistry::class)->enabled($module), 404);

        return $next($request);
    }
}
