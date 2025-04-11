<?php

namespace App\Service;

use App\Service\Formatter;
use App\Config\TransactionType;

class CommissionCalculator
{
   
    private $currencyConverter;
    private $strategies = [];
    
    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
        
        // Register strategies
        $this->strategies = [
            TransactionType::DEPOSIT => new Deposit($currencyConverter),
            TransactionType::WITHDRAW => new Withdraw($currencyConverter)
        ];
    }

    public function calculate(array $transactions): array
    {

        $fees = [];

        foreach ($transactions as $transaction) {

            $fee = $this->strategies[$transaction->getOperationType()]->calculateFee($transaction);

            $fees[] = Formatter::formatOutput($fee, $transaction->getCurrency());
            
        }

        return $fees;
    }

}