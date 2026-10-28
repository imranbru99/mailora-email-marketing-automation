<?php

namespace App\Support\Integrations;

use App\Contracts\MailDomainLookup;

class DnsMailDomainLookup implements MailDomainLookup
{
    public function exchanges(string $domain): ?array
    {
        if (! preg_match('/^[a-z0-9.-]+$/i', $domain)) {
            return [];
        }
        $records = @dns_get_record($domain, DNS_MX);
        if ($records === false) {
            return null;
        }
        if (! $records) {
            $fallback = @dns_get_record($domain, DNS_A | DNS_AAAA);

            return $fallback === false ? null : ($fallback ? [$domain] : []);
        }
        $targets = [];
        foreach ($records as $record) {
            if (isset($record['target']) && $record['target'] !== '.' && $record['target'] !== '') {
                $targets[] = $record['target'];
            }
        }

        return $targets;
    }
}
