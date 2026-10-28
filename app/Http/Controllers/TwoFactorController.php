<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Totp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function show(): View
    {
        return view('public.two-factor');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:30']);
        $user = $request->user();
        $success = false;
        DB::transaction(function () use ($user, $data, &$success) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            if (! $locked->two_factor_secret) {
                $success = true;

                return;
            }
            $step = app(Totp::class)->verify($locked->two_factor_secret, $data['code'], $locked->last_totp_step);
            if ($step !== null) {
                $locked->forceFill(['last_totp_step' => $step])->save();
                $success = true;

                return;
            }
            $codes = $locked->recovery_codes ?? [];
            foreach ($codes as $i => $hash) {
                if (hash_equals($hash, hash('sha256', $data['code']))) {
                    unset($codes[$i]);
                    $locked->forceFill(['recovery_codes' => array_values($codes)])->save();
                    $success = true;
                    break;
                }
            }
        });
        if (! $success) {
            throw ValidationException::withMessages(['code' => 'Invalid or previously used code.']);
        }
        $request->session()->regenerate();
        $request->session()->put('two_factor_user', $user->id);

        return redirect('/admin');
    }
}
