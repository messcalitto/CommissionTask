<?php

namespace App\Service;

class Formatter
{
    public static function formatOutput(float $amount, string $currency): string
    {
        if ($currency === 'JPY') {
            return number_format($amount, 0, '.', '');
        }

        return number_format($amount, 2, '.', '');
    }
}