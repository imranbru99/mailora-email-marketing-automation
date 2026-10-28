<?php

namespace App\Services;

use App\Models\SendingIdentity;

class DomainVerifier
{
    public function challenge(SendingIdentity $sender): string
    {
        if (! $sender->verification_token) {
            $sender->update(['verification_token' => bin2hex(random_bytes(24))]);
        }

        return 'mailora-verification='.$sender->verification_token;
    }

    /** @return array{ownership:bool,spf:bool,dmarc:bool} */
    public function check(SendingIdentity $sender): array
    {
        $domain = strtolower(trim($sender->domain));
        if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || ! str_contains($domain, '.') || strtolower(substr(strrchr($sender->email, '@'), 1)) !== $domain) {
            throw new \InvalidArgumentException('Sender email must match a valid domain.');
        }
        $challenge = $this->challenge($sender);
        $txt = $this->txt($domain);
        $dmarc = $this->txt('_dmarc.'.$domain);
        $result = ['ownership' => in_array($challenge, $this->txt('_mailora.'.$domain), true), 'spf' => count(array_filter($txt, fn ($v) => str_starts_with($v, 'v=spf1 '))) === 1, 'dmarc' => count(array_filter($dmarc, fn ($v) => str_starts_with($v, 'v=DMARC1;'))) === 1];
        $sender->update(['dns_verified_at' => ! in_array(false, $result, true) ? now() : null]);
        if (! in_array(false, $result, true)) {
            $sender->update(['verified_at' => now()]);
        }

        return $result;
    }

    /** @return list<string> */
    private function txt(string $domain): array
    {
        $records = dns_get_record($domain, DNS_TXT);

        return array_map(fn ($record) => $record['txt'] ?? '', $records ?: []);
    }
}
