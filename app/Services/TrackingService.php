<?php

namespace App\Services;

use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class TrackingService
{
    public function links(string $html, MessageLog $message): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        foreach ($document->getElementsByTagName('a') as $anchor) {
            $url = $anchor->getAttribute('href');
            if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) || str_contains($url, '/unsubscribe/') || str_contains($url, '/preferences/')) {
                continue;
            }
            $token = hash_hmac('sha256', $message->public_id.'|'.$url, config('app.key'));
            DB::table('tracking_links')->updateOrInsert(['token' => $token], ['business_id' => $message->business_id, 'message_log_id' => $message->id, 'destination' => $url, 'created_at' => now(), 'updated_at' => now()]);
            $anchor->setAttribute('href', URL::signedRoute('track.click', ['token' => $token]));
        }

        return str_replace('<?xml encoding="UTF-8">', '', $document->saveHTML());
    }
}
