<?php

namespace App\Services;

use App\Contracts\MailDomainLookup;

class EmailHygiene
{
    public function __construct(private MailDomainLookup $dns) {}

    /** @return array{status:string,findings:list<string>} */
    public function check(string $email): array
    {
        $email = mb_strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'invalid', 'findings' => ['Email syntax is invalid.']];
        }
        [$local,$domain] = explode('@', $email, 2);
        $findings = [];
        if (in_array($local, ['admin', 'info', 'support', 'sales', 'hello', 'contact', 'office', 'noreply', 'no-reply'])) {
            $findings[] = 'Role-based address; confirm an individual has opted in.';
        }
        if (in_array($domain, config('hygiene.disposable_domains', []))) {
            $findings[] = 'Known disposable-mail domain.';
        }
        $suggestions = config('hygiene.domain_typos', []);
        if (isset($suggestions[$domain])) {
            $findings[] = 'Possible typo: did you mean '.$suggestions[$domain].'?';
        }
        if (preg_match('/\.(test|invalid|example|localhost|local)$/', $domain)) {
            return ['status' => 'unknown', 'findings' => [...$findings, 'Reserved or local domain; no public deliverability check performed.']];
        }
        $mx = $this->dns->exchanges($domain);
        if ($mx === null) {
            return ['status' => 'unknown', 'findings' => [...$findings, 'DNS lookup was unavailable; try again later.']];
        }
        if (! $mx) {
            return ['status' => 'invalid', 'findings' => [...$findings, 'The domain does not advertise a mail service.']];
        }

        return ['status' => $findings ? 'review' : 'valid', 'findings' => [...$findings, 'Domain accepts mail routing. Mailbox existence and consent are not verified.']];
    }
}
