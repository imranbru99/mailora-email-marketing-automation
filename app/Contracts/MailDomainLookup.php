<?php

namespace App\Contracts;

interface MailDomainLookup
{
    /** @return list<string>|null Null means lookup unavailable; empty means no mail service. */
    public function exchanges(string $domain): ?array;
}
