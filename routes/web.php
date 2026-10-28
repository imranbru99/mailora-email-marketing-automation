<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\GrowthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Middleware\RequireTwoFactor;
use App\Models\Branch;
use App\Models\DataExport;
use App\Models\MessageLog;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Template;
use App\Models\User;
use App\Services\ModuleRegistry;
use App\Services\TemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'public.home')->name('home');
Route::get('/features', fn () => view('public.features', ['modules' => Module::orderBy('id')->get()]))->name('features');
Route::get('/features/{key}', fn (string $key) => view('public.feature', ['module' => Module::where('key', $key)->firstOrFail()]));
Route::get('/pricing', fn () => view('public.pricing', ['plans' => Plan::all()]));
Route::get('/templates', fn () => view('public.templates', ['templates' => collect(config('template-gallery'))->map(fn ($template) => (object) $template)]));
Route::get('/solutions', fn () => view('public.solutions'));
Route::get('/docs', fn () => view('public.docs'));
Route::get('/demo', fn () => view('public.demo'))->name('demo');
Route::post('/demo', function (Request $request) {
    abort_unless(config('platform.demo') && app()->environment('local', 'testing'), 404);
    $data = $request->validate(['email' => 'required|in:owner@demo.test,analyst@demo.test,manager@demo.test']);
    Auth::login(User::where('email', $data['email'])->firstOrFail());
    $request->session()->regenerate();

    return redirect('/admin');
})->middleware('throttle:5,1');
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
Route::post('/branch', function (Request $request) {
    abort_if($request->user()->branch_id, 403);
    $data = $request->validate(['branch_id' => 'nullable|integer']);
    $id = $data['branch_id'] ?? null;
    if ($id) {
        abort_unless(Branch::where('business_id', $request->user()->business_id)->whereKey($id)->exists(), 403);
    }
    $request->session()->put('branch_id', $id ? (int) $id : null);

    return redirect('/admin');
})->middleware('auth')->name('branch.switch');
Route::get('/forms/{id}', [PublicController::class, 'form'])->name('forms.show');
Route::post('/forms/{id}', [PublicController::class, 'subscribe'])->middleware('throttle:5,1');
Route::get('/confirm/{id}', [PublicController::class, 'confirm'])->middleware('signed')->name('confirm');
Route::match(['get', 'post'], '/unsubscribe/{token}', [PublicController::class, 'unsubscribe'])->middleware('throttle:60,1')->name('unsubscribe');
Route::get('/track/open/{id}', [PublicController::class, 'open'])->middleware(['signed', 'throttle:120,1'])->name('track.open');
Route::get('/health', fn () => response()->json(['status' => 'ok', 'database' => DB::select('select 1') ? 'connected' : 'unavailable']));
Route::get('/sitemap.xml', fn () => response()->view('public.sitemap', ['modules' => Module::all()])->header('Content-Type', 'application/xml'));

Route::get('/p/{id}', [GrowthController::class, 'landing'])->name('landing.show');
Route::get('/s/{id}', [GrowthController::class, 'survey'])->name('survey.show');
Route::post('/s/{id}', [GrowthController::class, 'answer'])->middleware('throttle:10,1');
Route::get('/events/{id}', [GrowthController::class, 'webinar'])->name('webinar.show');
Route::post('/events/{id}', [GrowthController::class, 'register'])->middleware('throttle:5,1');
Route::get('/events/{id}/calendar', [GrowthController::class, 'calendar'])->name('webinar.calendar');
Route::get('/go/{id}', [GrowthController::class, 'short'])->name('short.redirect');
Route::get('/help/{id}', [GrowthController::class, 'article'])->name('article.show');
Route::get('/invoices/{id}/pdf', [GrowthController::class, 'invoice'])->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:invoices'])->name('invoice.pdf');
Route::get('/exports/contacts', [ExportController::class, 'contacts'])->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:exports', 'module:contacts'])->name('contacts.export');
Route::get('/exports/contacts/{id}', [ExportController::class, 'profile'])->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:consent'])->name('contacts.profile');

Route::get('/two-factor', [TwoFactorController::class, 'show'])->middleware('auth')->name('two-factor.challenge');
Route::post('/two-factor', [TwoFactorController::class, 'verify'])->middleware(['auth', 'throttle:5,1']);

Route::get('/track/click/{token}', [PublicController::class, 'click'])->middleware(['signed', 'throttle:120,1'])->name('track.click');
Route::get('/preferences/{token}', [PublicController::class, 'preferences'])->name('preferences');
Route::post('/preferences/{token}', [PublicController::class, 'savePreferences'])->middleware('throttle:10,1');

Route::get('/messages/{id}/preview', function (string $id) {
    $message = MessageLog::where('public_id', $id)->firstOrFail();

    return response($message->rendered_html ?? '', 200, ['Content-Type' => 'text/html; charset=utf-8', 'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'"]);
})->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:queue'])->name('message.preview');

Route::get('/exports/files/{id}', function (string $id) {
    $export = DataExport::where('public_id', $id)->where('user_id', auth()->id())->firstOrFail();
    $module = $export->type === 'contacts' ? 'contacts' : 'campaign-reports';
    abort_unless(app(ModuleRegistry::class)->enabled($module) && auth()->user()->allows($module, 'export'), 403);
    abort_unless($export->status === 'ready' && $export->expires_at?->isFuture() && $export->path && Storage::disk('local')->exists($export->path), 404);

    return Storage::disk('local')->download($export->path, 'mailora-'.$export->type.'.'.pathinfo($export->path, PATHINFO_EXTENSION));
})->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:exports'])->name('exports.download');

Route::get('/templates/{id}/preview', function (string $id) {
    abort_unless(auth()->user()->allows('templates'), 403);
    $template = Template::where('public_id', $id)->firstOrFail();

    return response(app(TemplateRenderer::class)->sanitize($template->html ?? ''), 200, ['Content-Type' => 'text/html; charset=utf-8', 'Content-Security-Policy' => "sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'"]);
})->middleware(['auth', RequireTwoFactor::class, 'tenant', 'module:templates'])->name('template.preview');

require __DIR__.'/feedback.php';
