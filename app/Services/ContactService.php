<?php

namespace App\Services;

use App\Models\ConsentRecord;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\Suppression;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function unsubscribe(Contact $contact, string $source = 'preference center'): void
    {
        DB::transaction(function () use ($contact, $source) {
            $contact->update(['status' => 'unsubscribed']);
            Suppression::withoutGlobalScope('branch')->firstOrCreate(['email' => $contact->email], ['branch_id' => null, 'reason' => 'unsubscribe']);
            ConsentRecord::create(['contact_id' => $contact->id, 'branch_id' => $contact->branch_id, 'action' => 'withdrawn', 'source' => $source]);
            Audit::record('contact.unsubscribed', $contact);
        });
    }

    public function erase(Contact $contact): void
    {
        DB::transaction(function () use ($contact) {
            $this->unsubscribe($contact, 'data subject erasure');
            $contact->lists()->detach();
            MessageLog::where('contact_id', $contact->id)->delete();
            $contact->update(['first_name' => 'Erased', 'last_name' => null, 'phone' => null, 'company' => null, 'notes' => null, 'tags' => [], 'custom_fields' => [], 'email' => 'erased-'.$contact->public_id.'@invalid.test']);
            $contact->delete();
            Audit::record('contact.erased', $contact);
        });
    }
}
