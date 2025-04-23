<?php
namespace App\Service;

use App\Entity\Transaction;

interface StrategyInterface
{
    public function calculateFee(Transaction $transaction): float;
}