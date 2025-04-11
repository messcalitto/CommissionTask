<?php

declare(strict_types=1);

namespace App\Service;

class Math
{
    private $scale;

    public function __construct(int $scale = 2)
    {
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    /**
     * Round up a number to the nearest decimal places.
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public static function roundUp(float $value, int $precision = 2, string $currency = 'EUR'): float
    {

        if ($currency === 'JPY') {
            return ceil($value);
        }

        $factor = pow(10, $precision);
        return ceil($value * $factor) / $factor;
    }
}
