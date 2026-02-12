<?php

namespace App\Services\Epicor\Estu;

use Illuminate\Support\Str;

class EpicorFormatter
{
    public function text(?string $value, int $len): string
    {
        return Str::of($value ?? '')
            ->substr(0, $len)
            ->padRight($len, ' ');
    }

    /**
     * Epicor numeric without sign. Implied decimals.
     * Example: 146.47 with (len=9, dec=2) => 000014647
     */
    public function numeric($value, int $len, int $dec = 0, bool $zeroIfEmpty = false): string
    {
        if ($value === null || $value === '') {
            return $zeroIfEmpty ? Str::repeat('0', $len) : Str::repeat(' ', $len);
        }

        $scaled = (int) round((float) $value * (10 ** $dec));

        return Str::of((string) $scaled)->padLeft($len, '0');
    }

    /**
     * Epicor signed numeric where sign (+/-) is the last character.
     * Example: 163.36 with (intDigits=7, dec=2) => 000016336+
     */
    public function signedNumeric($value, int $intDigits, int $dec): string
    {
        $num  = (float) ($value ?? 0);
        $sign = $num < 0 ? '-' : '+';

        $scaled = (int) round(abs($num) * (10 ** $dec));
        $digits = $intDigits + $dec;

        return Str::of((string) $scaled)
            ->padLeft($digits, '0')
            ->append($sign);
    }

    public function filler(int $len): string
    {
        return Str::repeat(' ', $len);
    }

    /* ===================== PARSE HELPERS ===================== */

    public function parseText(string $raw): string
    {
        return rtrim($raw);
    }

    public function parseNumeric(string $raw, int $dec = 0): float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0.0;
        }
        return ((float) $raw) / (10 ** $dec);
    }

    public function parseSignedNumeric(string $value, int $decimals = 0): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        // Epicor sign handling: last char is + or -
        $sign = 1;

        $lastChar = substr($value, -1);
        if ($lastChar === '-') {
            $sign = -1;
            $value = substr($value, 0, -1);
        } elseif ($lastChar === '+') {
            $value = substr($value, 0, -1);
        }

        // remove non-numeric just in case
        $value = preg_replace('/[^0-9]/', '', $value);

        if ($value === '') {
            return 0.0;
        }

        $number = (float)$value;

        if ($decimals > 0) {
            $number = $number / pow(10, $decimals);
        }

        return $number * $sign;
    }

}
