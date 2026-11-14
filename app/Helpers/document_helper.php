<?php

/**
 * Shared by every printable document (the order invoice and the Sales,
 * Ingredient and Product exports) so they carry the same letterhead and
 * number formatting - edit the company details once, here.
 */

if (!function_exists('document_company')) {
    function document_company(): array
    {
        return [
            'name'    => 'Gab Shawn Foods OPC',
            'address' => 'Company address line, City, Province, 0000',
            'phone'   => '(000) 000 0000',
            'email'   => 'orders@gabshawnfoods.com',
            'logo'    => base_url('assets/images/Gabs_Logo.svg'),
        ];
    }
}

if (!function_exists('document_money')) {
    function document_money($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('document_date')) {
    function document_date($value): string
    {
        return $value ? date('F j, Y', strtotime($value)) : '—';
    }
}

if (!function_exists('document_peso_words')) {
    /** Amount in words, Philippine pesos. */
    function document_peso_words($amount): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
                 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $under1000 = function ($n) use ($ones, $tens, &$under1000) {
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? '-' . $ones[$n % 10] : '');
            return $ones[intdiv($n, 100)] . ' hundred' . ($n % 100 ? ' ' . $under1000($n % 100) : '');
        };

        $pesos    = (int) floor($amount);
        $wholePesos = $pesos;
        $centavos = (int) round(($amount - $pesos) * 100);

        if ($pesos === 0) {
            $words = 'zero';
        } else {
            $parts  = [];
            $scales = [1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand'];
            foreach ($scales as $value => $label) {
                if ($pesos >= $value) {
                    $parts[] = $under1000(intdiv($pesos, $value)) . ' ' . $label;
                    $pesos  %= $value;
                }
            }
            if ($pesos > 0) $parts[] = $under1000($pesos);
            $words = implode(' ', $parts);
        }

        $out = ucfirst($words) . ' peso' . ($wholePesos === 1 ? '' : 's');
        if ($centavos > 0) {
            $out .= ' and ' . $under1000($centavos) . ' centavo' . ($centavos > 1 ? 's' : '');
        }

        return $out . ' only';
    }
}

if (!function_exists('database_now')) {
    /**
     * The current date and time (Y-m-d H:i:s) by the database's own clock.
     * The app's PHP clock runs in UTC while the database follows the server's
     * local time, so anything meant to line up with stored order dates -
     * "today", "generated at" - reads the time from the database instead.
     */
    function database_now(): string
    {
        static $now = null;

        return $now ??= (string) db_connect()->query('SELECT NOW() AS now')->getRow()->now;
    }
}

if (!function_exists('database_today')) {
    function database_today(): string
    {
        return substr(database_now(), 0, 10);
    }
}
