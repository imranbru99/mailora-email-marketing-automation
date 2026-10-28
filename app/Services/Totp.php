<?php

namespace App\Services;

final class Totp
{
    public function secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $value = '';
        for ($i = 0; $i < 32; $i++) {
            $value .= $alphabet[random_int(0, 31)];
        }

return $value;
    }

    public function code(string $secret, int $step, int $digits = 6): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $n = strpos($alphabet, $char);
            if ($n === false) {
                throw new \InvalidArgumentException('Invalid TOTP secret.');
            }$bits .= str_pad(decbin($n), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $key .= chr(bindec($byte));
            }
        }
        $hash = hash_hmac('sha1', pack('N2', 0, $step), $key, true);
        $offset = ord($hash[19]) & 15;
        $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($number % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function verify(string $secret, string $code, ?int $lastStep = null): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $current = intdiv(now()->timestamp, 30);
        foreach ([$current, $current - 1, $current + 1] as $step) {
            if (($lastStep === null || $step > $lastStep) && hash_equals($this->code($secret,$step), $code)) {
                return $step;
            }
        }

        return null;
    }
}
