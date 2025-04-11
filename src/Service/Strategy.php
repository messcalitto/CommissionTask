<?php
namespace App\Service;

use App\Entity\Transaction;


abstract class Strategy
{

    protected $currencyConverter;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    abstract public function calculateFee(Transaction $transaction): float;

}