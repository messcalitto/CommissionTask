<?php

namespace App\Service;

use App\Config\Config;
use App\Entity\Transaction;
use App\Service\Math;



class Deposit extends Strategy
{
    public function calculateFee(Transaction $transaction): float
    {
        return Math::roundUp($transaction->getAmount() * Config::getFloat('DEPOSIT_FEE_PERCENTAGE') / 100);
    }
}