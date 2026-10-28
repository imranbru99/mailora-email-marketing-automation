<?php

namespace App\Http\Controllers;

use App\Contracts\ReportPdf;
use App\Models\Article;
use App\Models\Invoice;
use App\Models\LandingPage;
use App\Models\ShortLink;
use App\Models\SignupForm;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\TenantModel;
use App\Models\Webinar;
use App\Models\WebinarRegistration;
use App\Services\InvoiceCalculator;
use App\Services\ModuleRegistry;
use App\Services\TemplateRenderer;
use App\Services\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowthController extends Controller
{
    /**
     * @template T of \App\Models\TenantModel
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function resolve(string $class, string $id, string $module): TenantModel
    {
        $record = $class::withoutGlobalScopes()->where('public_id', $id)->whereNull('deleted_at')->firstOrFail();
        app(TenantContext::class)->set($record->business_id, $record->branch_id);
        abort_unless(app(ModuleRegistry::class)->enabled($module), 404);

        return $record;
    }

    public function landing(string $id): View
    {
        $page = $this->resolve(LandingPage::class, $id, 'landing-pages');
        abort_unless($page->published, 404);

        return view('public.landing', ['page' => $page, 'html' => app(TemplateRenderer::class)->sanitize($page->body ?? ''), 'form' => $page->signup_form_id ? SignupForm::find($page->signup_form_id) : null]);
    }

    public function survey(string $id): View
    {
        $survey = $this->resolve(Survey::class, $id, 'surveys');
        abort_unless($survey->published, 404);

        return view('public.survey', compact('survey'));
    }

    public function answer(Request $request, string $id): RedirectResponse
    {
        $survey = $this->resolve(Survey::class, $id, 'surveys');
        abort_unless($survey->published, 404);
        $data = $request->validate(['answer' => ['required', 'string', Rule::in($survey->options ?? [])]]);
        $visitor = $request->session()->get('survey_visitor');
        if (! $visitor) {
            $visitor = bin2hex(random_bytes(32));
            $request->session()->put('survey_visitor', $visitor);
        }
        $hash = hash_hmac('sha256', $visitor, config('app.key'));
        SurveyResponse::firstOrCreate(['survey_id' => $survey->id, 'visitor_hash' => $hash], ['answer' => $data['answer']]);

        return back()->with('success', 'Thank you. Your response has been recorded.');
    }

    public function webinar(string $id): View
    {
        $webinar = $this->resolve(Webinar::class, $id, 'webinars');
        abort_unless($webinar->published, 404);

        return view('public.webinar', compact('webinar'));
    }

    public function register(Request $request, string $id): RedirectResponse
    {
        $webinar = $this->resolve(Webinar::class, $id, 'webinars');
        abort_unless($webinar->published, 404);
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:254', 'website' => 'nullable|max:0']);
        $data['email'] = mb_strtolower(trim($data['email']));
        unset($data['website']);
        DB::transaction(function () use ($webinar, $data) {
            $event = Webinar::lockForUpdate()->findOrFail($webinar->id);
            if ($event->starts_at?->isPast()) {
                throw ValidationException::withMessages(['email' => 'Registration has closed.']);
            }
            if (WebinarRegistration::where('webinar_id', $event->id)->where('email', $data['email'])->exists()) {
                return;
            }
            if ($event->capacity > 0 && WebinarRegistration::where('webinar_id', $event->id)->count() >= $event->capacity) {
                throw ValidationException::withMessages(['email' => 'This event is full.']);
            }
            WebinarRegistration::create([...$data, 'webinar_id' => $event->id]);
        });

        return back()->with('success', 'Your registration is confirmed. Add the event to your calendar below.');
    }

    public function calendar(string $id): Response
    {
        $event = $this->resolve(Webinar::class, $id, 'webinars');
        abort_unless($event->published && $event->starts_at && $event->ends_at, 404);
        $escape = fn ($v) => str_replace(['\\', "\r", "\n", ',', ';'], ['\\\\', '', '\\n', '\\,', '\\;'], $v ?? '');
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Imran Ahmed//Mailora//EN\r\nBEGIN:VEVENT\r\nUID:".$event->public_id."@mailora\r\nDTSTAMP:".now()->utc()->format('Ymd\THis\Z')."\r\nDTSTART:".$event->starts_at->utc()->format('Ymd\THis\Z')."\r\nDTEND:".$event->ends_at->utc()->format('Ymd\THis\Z')."\r\nSUMMARY:".$escape($event->name)."\r\nLOCATION:".$escape($event->location)."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="event.ics"']);
    }

    public function short(string $id): RedirectResponse
    {
        $link = $this->resolve(ShortLink::class, $id, 'short-links');
        abort_unless($link->active, 404);
        abort_unless(in_array(parse_url($link->destination, PHP_URL_SCHEME), ['http', 'https'], true), 422);
        $link->increment('clicks');

        return redirect()->away($link->destination);
    }

    public function article(string $id): View
    {
        $article = $this->resolve(Article::class, $id, 'knowledge-base');
        abort_unless($article->published, 404);

        return view('public.article', ['article' => $article, 'html' => app(TemplateRenderer::class)->sanitize($article->body ?? '')]);
    }

    public function invoice(string $id): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()?->allows('invoices', 'view'), 403);
        $invoice = Invoice::where('public_id', $id)->firstOrFail();

        return app(ReportPdf::class)->download('reports.invoice',['invoice' => $invoice, 'total' => app(InvoiceCalculator::class)->total($invoice)],'invoice-'.$invoice->public_id.'.pdf');
    }
}
