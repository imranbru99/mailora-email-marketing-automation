<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImportContacts implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $businessId, public ?int $branchId, public int $importId) {}

    public function handle(): void
    {
        app(TenantContext::class)->run($this->businessId, $this->branchId, function () {
            if (! app(ModuleRegistry::class)->enabled('imports')) {
                return;
            }
            $import = ContactImport::findOrFail($this->importId);
            if ($import->status === 'completed') {
                return;
            }
            if (! str_starts_with($import->path, 'imports/'.$this->businessId.'/') || str_contains($import->path, '..')) {
                throw new \RuntimeException('Invalid import path.');
            }
            $stream = Storage::disk('local')->readStream($import->path);
            if (! $stream) {
                throw new \RuntimeException('Import file is unavailable.');
            }
            try {
                $header = fgetcsv($stream, 0, ',', '"', '');
                if (! $header) {
                    $import->update(['status' => 'failed', 'errors' => ['Empty CSV file.']]);

                    return;
                }
                $header = array_map(fn ($v) => trim(ltrim($v, "\xEF\xBB\xBF")), $header);
                if (! in_array('email', $header, true) || ! in_array('first_name', $header, true)) {
                    $import->update(['status' => 'failed', 'errors' => ['Required headers: email, first_name. Optional: last_name, company, phone.']]);

                    return;
                }
                $import->update(['status' => 'processing']);
                $created = 0;
                $skipped = 0;
                $errors = [];
                $line = 1;
                while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                    $line++;
                    if (count($row) !== count($header)) {
                        if (count($errors) < 100) {
                            $errors[] = "Line $line: wrong column count.";
                        }$skipped++;

                        continue;
                    }
                    $data = array_intersect_key(array_combine($header, $row), array_flip(['email', 'first_name', 'last_name', 'company', 'phone']));
                    $data['email'] = mb_strtolower(trim($data['email']));
                    if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 254 || ! trim($data['first_name']) || mb_strlen($data['first_name']) > 100) {
                        if (count($errors) < 100) {
                            $errors[] = "Line $line: invalid email or name.";
                        }$skipped++;

                        continue;
                    }
                    if (Contact::withoutGlobalScope('branch')->withTrashed()->where('email', $data['email'])->exists()) {
                        $skipped++;

                        continue;
                    }
                    Contact::create([...$data, 'status' => 'pending', 'source' => 'csv import']);
                    $created++;
                }
                $import->update(['status' => 'completed', 'imported' => $created, 'skipped' => $skipped, 'errors' => $errors]);
            } finally {
                fclose($stream);
            }
        });
    }
}
