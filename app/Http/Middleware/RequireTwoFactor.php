<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        if ($user?->two_factor_secret && $request->session()->get('two_factor_user') !== $user->id) {
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
