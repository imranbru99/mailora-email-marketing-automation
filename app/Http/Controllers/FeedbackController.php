<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFeedback;
use App\Models\FeedbackEndpoint;
use App\Models\FeedbackEvent;
use App\Models\MessageLog;
use App\Services\ModuleRegistry;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function __invoke(Request $request, string $endpoint): JsonResponse
    {
        abort_if(strlen($request->getContent()) > 16384, 413);
        $connection = FeedbackEndpoint::withoutGlobalScopes()->where('public_id', $endpoint)->firstOrFail();

        return app(TenantContext::class)->run($connection->business_id, $connection->branch_id, function () use ($request, $connection) {
            abort_unless($connection->active && app(ModuleRegistry::class)->enabled('feedback'), 404);
            $timestamp = $request->header('X-Mailora-Timestamp', '');
            $signature = $request->header('X-Mailora-Signature', '');
            abort_unless(ctype_digit($timestamp) && strlen($timestamp) <= 12 && abs(now()->timestamp - (int) $timestamp) <= 300, 401);
            $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $connection->secret);
            abort_unless(strlen($signature) === 64 && hash_equals($expected, $signature), 401);
            abort_unless($request->isJson(), 415);
            $data = $request->validate([
                'event_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.:-]+$/'],
                'message_id' => ['required', 'ulid'],
                'type' => ['required', 'in:delivered,hard_bounce,soft_bounce,complaint'],
                'occurred_at' => ['nullable', 'date'],
            ]);
            $message = MessageLog::where('public_id', $data['message_id'])->firstOrFail();
            abort_unless($message->recipient_email && in_array($message->status, ['sent', 'dispatching', 'uncertain'], true), 409, 'The message has no live delivery attempt to reconcile.');
            $event = DB::transaction(function () use ($connection, $message, $data) {
                $event = FeedbackEvent::firstOrCreate(['feedback_endpoint_id' => $connection->id, 'external_event_id' => $data['event_id']], [
                    'branch_id' => $message->branch_id, 'message_log_id' => $message->id, 'type' => $data['type'], 'occurred_at' => $data['occurred_at'] ?? null,
                ]);
                abort_unless($event->message_log_id === $message->id && $event->type === $data['type'], 409, 'The event identifier has already been used for a different receipt.');
                if ($event->status !== 'processed') {
                    ProcessFeedback::dispatch($event->business_id, $event->branch_id, $event->id)->afterCommit();
                }

                return $event;
            });

            return response()->json(['accepted' => true, 'receipt' => $event->public_id], 202);
        });
    }
}
