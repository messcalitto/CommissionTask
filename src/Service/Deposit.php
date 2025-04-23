<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Service\Math;
use App\Service\StrategyInterface;
use App\Config\Config;

class Deposit implements StrategyInterface
{
    private $math;
    private $config;

    public function __construct(Math $math, Config $config)
    {
        $this->math = $math;
        $this->config = $config;
    }

    public function calculateFee(Transaction $transaction): float
    {
        $feePercentage = $this->config->getDepositFeePercentage();
        $fee = $this->math->mul($transaction->getAmount(), $feePercentage);
        $fee = $this->math->div($fee, 100);
        return $this->math->roundUp($fee);
    }
}