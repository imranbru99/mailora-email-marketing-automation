<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

class SetTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = $request->user()) {
            $branch = $user->branch_id ?? ($request->hasSession() ? $request->session()->get('branch_id') : null);
            app(TenantContext::class)->set($user->business_id, $branch ? (int) $branch : null);
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
