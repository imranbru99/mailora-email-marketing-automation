<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Models\Contact;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function contacts(): StreamedResponse
    {
        abort_unless(auth()->user()?->allows('contacts', 'export'), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'first_name', 'last_name', 'company', 'status', 'locale', 'score'], ',', '"', '');
            foreach (Contact::lazyById(1000) as $contact) {
                $row = [];
                foreach (['email', 'first_name', 'last_name', 'company', 'status', 'locale', 'score'] as $key) {
                    $value = (string) $contact->$key;
                    $row[] = preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value;
                }fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
            Audit::record('contacts.exported');
        }, 'contacts.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function profile(string $id): JsonResponse
    {
        abort_unless(auth()->user()?->allows('contacts', 'export'), 403);
        $contact = Contact::where('public_id', $id)->firstOrFail();

        return response()->json(['profile' => $contact->makeHidden(['unsubscribe_token']), 'consent' => ConsentRecord::where('contact_id', $contact->id)->get()])->header('Content-Disposition','attachment; filename="contact.json"');
    }
}
