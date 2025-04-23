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

    public function addStrategy(string $operationType, StrategyInterface $strategy): void
    {
        $this->strategies[$operationType] = $strategy;
    }

    public function calculate(array $transactions): array
    {
        $fees = [];

        foreach ($transactions as $transaction) {
            
            $operationType = $transaction->getOperationType();

            if (!isset($this->strategies[$operationType])) {
                throw new \InvalidArgumentException("No strategy found for operation type: $operationType");
            }

            $fee = $this->strategies[$operationType]->calculateFee($transaction);

            $fees[] = $this->formatter->formatOutput($fee, $transaction->getCurrency());
        }

        return $fees;
    }
}