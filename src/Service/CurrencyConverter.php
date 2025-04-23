<?php

namespace App\Service;

class CurrencyConverter
{
    private array $exchangeRates;

    public function __construct(array $exchangeRates)
    {
        $this->exchangeRates = $exchangeRates;
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if (!isset($this->exchangeRates[$fromCurrency]) || !isset($this->exchangeRates[$toCurrency])) {
            throw new \InvalidArgumentException("Exchange rate for $fromCurrency or $toCurrency not found.");
        }

        $amountInBase = $amount / $this->exchangeRates[$fromCurrency];
        return $amountInBase * $this->exchangeRates[$toCurrency];
    }
}