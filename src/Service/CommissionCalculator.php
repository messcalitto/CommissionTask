<?php

namespace App\Service;

use App\Service\Formatter;
use App\Service\StrategyInterface;

class CommissionCalculator
{
    private $strategies;
    private $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->strategies = [];
        $this->formatter = $formatter;
    }

    /**
     * Adds a strategy for a specific operation type
     * 
     * @param string $operationType The operation type (e.g., "deposit", "withdrawal")
     * @param StrategyInterface $strategy The strategy to be added
     */

    public function addStrategy(string $operationType, StrategyInterface $strategy): void
    {
        $this->strategies[$operationType] = $strategy;
    }

    /**
     * Calculates the fees for a list of transactions
     * 
     * @param array $transactions An array of Transaction objects
     * @return array An array of formatted fee strings
     */

    public function calculate(array $transactions): array
    {
        $fees = [];

        foreach ($transactions as $transaction) {
            
            $operationType = $transaction->getOperationType();

            if (!isset($this->strategies[$operationType])) {
                throw new \InvalidArgumentException("No strategy found for operation type: $operationType");
            }

            // Calculate the fee according to the strategy (deposit, withdrawal)
            $fee = $this->strategies[$operationType]->calculateFee($transaction);

            // Format the fee and add it to the fees array
            $fees[] = $this->formatter->formatOutput($fee, $transaction->getCurrency());
        }

        return $fees;
    }
}