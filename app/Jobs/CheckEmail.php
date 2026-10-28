<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\EmailCheck;
use App\Services\EmailHygiene;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public int $businessId, public ?int $branchId, public int $contactId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            if (! app(ModuleRegistry::class)->enabled('verification')) {
                return;
            }
            $contact = Contact::find($this->contactId);
            if (! $contact) {
                return;
            }
            $email = $contact->email;
            $result = app(EmailHygiene::class)->check($email);
            $contact->refresh();
            if ($contact->trashed() || $contact->email !== $email || ! app(ModuleRegistry::class)->enabled('verification')) {
                return;
            }
            EmailCheck::updateOrCreate(['contact_id' => $contact->id], ['branch_id' => $contact->branch_id, 'email' => $contact->email, ...$result]);
        });
    }
}
