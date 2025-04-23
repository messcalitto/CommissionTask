<?php

namespace App\Service;

use App\Config\Config;

class Math
{
    private $scale;
    private $config;

    public function __construct(Config $config, int $scale = 3)
    {
        $this->config = $config;
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    public function div(string $leftOperand, string $rightOperand): string
    {
        if ($rightOperand === '0') {
            throw new \InvalidArgumentException('Division by zero.');
        }

        return bcdiv($leftOperand, $rightOperand, $this->scale);
    }
    
    public function mul(string $leftOperand, string $rightOperand): string
    {
        return bcmul($leftOperand, $rightOperand, $this->scale);
    }

    public function sub(string $leftOperand, string $rightOperand): string
    {
        return bcsub($leftOperand, $rightOperand, $this->scale);
    }

    /**
     * Round up a number to the nearest decimal places.
     *
     * @param float $value
     * @param int $precision
     * @return float
     */
    public function roundUp(float $value, int $precision = 2, string $currency = ''): float
    {
        if ($currency === '') {
            $currency = $this->config->getBaseCurrency();
        }

        if ($currency === 'JPY') {
            return ceil($value);
        }

        $factor = pow(10, $precision);
        return ceil($value * $factor) / $factor;
    }
}
