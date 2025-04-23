<?php

namespace CommissionTask\Tests;

use PHPUnit\Framework\TestCase;
use App\Service\Withdraw;
use App\Service\Math;
use App\Service\UserHistoryManager;
use App\Service\CurrencyConverter;
use App\Entity\Transaction;
use App\Config\Config;

class WithdrawTest extends TestCase
{
    public function testCalculateFeeForBusinessUser()
    {
        $currencyConverter = new CurrencyConverter( ['EUR' => 1.0, 'USD' => 1.2]);
        $userHistoryManager = new UserHistoryManager();

        $config = new Config();
        $withdraw = new Withdraw($currencyConverter, $userHistoryManager, new Math($config), $config);

        $transaction = new Transaction('2025-04-23', 1, 'business', 'withdraw', 1000.00, 'EUR');

        $fee = $withdraw->calculateFee($transaction);

        $this->assertEquals(5.00, $fee);
    }

    public function testCalculateFeeForPrivateUserWithinFreeLimit()
    {
        $currencyConverter = new CurrencyConverter( ['EUR' => 1.0, 'USD' => 1.2]);
        $userHistoryManager = new UserHistoryManager();

        $config = new Config();
        $withdraw = new Withdraw($currencyConverter, $userHistoryManager, new Math($config), $config);

        $transaction = new Transaction('2025-04-23', 1, 'private', 'withdraw', 400.00, 'EUR');
        
        $fee = $withdraw->calculateFee($transaction);

        $this->assertEquals(0.00, $fee);
    }

    public function testCalculateFeeForPrivateUserExceedingFreeLimit()
    {
        $currencyConverter = new CurrencyConverter( ['EUR' => 1.0, 'USD' => 1.2]);
        $userHistoryManager = new UserHistoryManager();

        $config = new Config();
        $withdraw = new Withdraw($currencyConverter, $userHistoryManager, new Math($config), $config);

        $transaction = new Transaction('2025-04-23', 1, 'private', 'withdraw', 1200.00, 'EUR');

        $fee = $withdraw->calculateFee($transaction);

        $this->assertEquals(0.60, $fee);
    }
}