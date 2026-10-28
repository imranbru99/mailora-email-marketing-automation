<?php

namespace App\Services;

use App\Models\Campaign;

class CampaignPreflight
{
    /** @return array{errors:list<string>,warnings:list<string>,checks:array<string,string>} */
    public function inspect(Campaign $campaign): array
    {
        $errors = app(ComplianceGuard::class)->campaignErrors($campaign);
        $warnings = [];
        $html = (string) $campaign->html;
        $doc = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $images = 0;
        $missingAlt = 0;
        $links = 0;
        $unsafeLinks = 0;
        foreach ($doc->getElementsByTagName('img') as $image) {
            $images++;
            if (! trim($image->getAttribute('alt'))) {
                $missingAlt++;
            }
        }
        foreach ($doc->getElementsByTagName('a') as $link) {
            $links++;
            $href = trim($link->getAttribute('href'));
            if ($href === '' || (! str_starts_with($href, '{{') && ! preg_match('/^(https?:\/\/|mailto:|tel:|#)/i', $href))) {
                $unsafeLinks++;
            }
        }
        if ($missingAlt) {
            $warnings[] = "$missingAlt image(s) need alternative text.";
        }
        if ($unsafeLinks) {
            $errors[] = "$unsafeLinks link(s) are empty or use an unsupported protocol.";
        }
        if (mb_strlen($campaign->subject) > 70) {
            $warnings[] = 'The subject may be truncated in some inboxes.';
        }
        if (! trim($campaign->preheader ?? '')) {
            $warnings[] = 'Add a preheader to provide useful inbox context.';
        }
        if (preg_match('/FREE MONEY|ACT NOW|100% FREE|GUARANTEED|!!!/i', $campaign->subject.' '.strip_tags($html))) {
            $warnings[] = 'Review urgent wording, exaggerated claims, and repeated punctuation.';
        }
        if ($images && mb_strlen(trim(strip_tags($html))) < 80) {
            $warnings[] = 'This message is image-heavy. Include meaningful text for accessibility.';
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => $warnings, 'checks' => ['Sender' => $campaign->identity->email ?? 'Not selected', 'Links' => (string) $links, 'Images' => (string) $images, 'Delivery mode' => config('platform.sandbox') ? 'Sandbox' : 'Live SMTP', 'Network checks' => 'DNS verification is managed under Sending identities. Links are not fetched.']];
    }
}
