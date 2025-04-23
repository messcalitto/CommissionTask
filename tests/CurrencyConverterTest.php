<?php

namespace CommissionTask\Tests;

use PHPUnit\Framework\TestCase;
use App\Service\CurrencyConverter;

class CurrencyConverterTest extends TestCase
{
    public function testConvertSameCurrency()
    {
        $exchangeRates = ['EUR' => 1.0, 'USD' => 1.2];
        $converter = new CurrencyConverter($exchangeRates);

        $amount = $converter->convert(100.0, 'EUR', 'EUR');

        $this->assertEquals(100.0, $amount);
    }

    public function testConvertDifferentCurrencies()
    {
        $exchangeRates = ['EUR' => 1.0, 'USD' => 1.2];
        $converter = new CurrencyConverter($exchangeRates);

        $amount = $converter->convert(100.0, 'EUR', 'USD');

        $this->assertEquals(120.0, $amount);
    }

    public function testConvertWithMissingRate()
    {
        $this->expectException(\Exception::class);

        $exchangeRates = ['EUR' => 1.0];
        $converter = new CurrencyConverter($exchangeRates);

        $converter->convert(100.0, 'EUR', 'JPY');
    }
}