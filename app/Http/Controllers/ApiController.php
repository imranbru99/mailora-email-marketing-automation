<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\AutomationEngine;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /** @return CursorPaginator<int,Contact> */
    public function contacts(Request $request): CursorPaginator
    {
        abort_unless($request->user()->tokenCan('contacts:read') && $request->user()->allows('contacts', 'view'), 403);

        $contacts = Contact::select(['id', 'public_id', 'email', 'first_name', 'last_name', 'status', 'score'])->orderBy('id')->cursorPaginate(100);
        $contacts->getCollection()->each(fn (Contact $contact) => $contact->makeHidden('id'));

        return $contacts;
    }

    public function storeContact(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('contacts:write') && $request->user()->allows('contacts', 'create'), 403);
        $data = $request->validate(['email' => 'required|email|max:254', 'first_name' => 'required|string|max:100', 'last_name' => 'nullable|string|max:100']);
        $data['email'] = mb_strtolower(trim($data['email']));
        if (Contact::withoutGlobalScope('branch')->withTrashed()->where('email', $data['email'])->exists()) {
            return response()->json(['message' => 'Contact already exists.'], 409);
        }
        $contact = Contact::create([...$data, 'status' => 'pending', 'source' => 'api']);
        app(AutomationEngine::class)->enroll($contact, 'contact.created');

        return response()->json($contact->only(['public_id', 'email', 'first_name', 'status']), 201);
    }

    public function event(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('events:write') && $request->user()->allows('automation', 'create'), 403);
        $data = $request->validate(['contact_id' => 'required|string', 'event' => 'required|in:manual,contact.created,form.submitted']);
        $contact = Contact::where('public_id', $data['contact_id'])->firstOrFail();
        app(AutomationEngine::class)->enroll($contact, $data['event']);

        return response()->json(['accepted' => true], 202);
    }
}
