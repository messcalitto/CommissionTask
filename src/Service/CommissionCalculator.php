<?php

namespace App\Service;

use App\Service\CurrencyConverter;

class CommissionCalculator
{
    private CurrencyConverter $currencyConverter;
    private const DEPOSIT_FEE_PERCENTAGE = 0.03;
    private const PRIVATE_WITHDRAW_FEE_PERCENTAGE = 0.3;
    private const BUSINESS_WITHDRAW_FEE_PERCENTAGE = 0.5;
    private const FREE_WITHDRAW_LIMIT = 1000.00;
    private const FREE_WITHDRAW_COUNT = 3;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function calculate(array $operation, array &$userHistory): string
    {
        $this->validateInputData($operation);

        [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;

        if ($operationType === 'deposit') {
            return $this->calculateDepositFee($amount, $currency);
        }

        if ($operationType === 'withdraw') {
            return $this->calculateWithdrawFee($userType, $amount, $currency, $userId, $date, $userHistory);
        }

        return 0.0;
    }

    private function formatOutput(float $amount, string $currency): string
    {
        if ($currency === 'JPY') {
            return number_format($amount, 0, '.', '');
        }

        return number_format($amount, 2, '.', '');
    }
    

    private function calculateDepositFee(float $amount, string $currency): string
    {
        $fee = ceil($amount * self::DEPOSIT_FEE_PERCENTAGE ) / 100;
        return $this->formatOutput($fee, $currency); 
    }

    private function calculateWithdrawFee(
        string $userType,
        float $amount,
        string $currency,
        int $userId,
        string $date,
        array &$userHistory
    ): string {

        if ($userType === 'business') {

            $fee = $amount * self::BUSINESS_WITHDRAW_FEE_PERCENTAGE / 100;
            return $this->formatOutput($fee, $currency);
        }

        if ($userType === 'private') {
            $weekNumber = (new \DateTime($date))->format('oW');
            
            //  Initialize the user's week history if it doesn't exist
            $userHistory[$userId][$weekNumber] = $userHistory[$userId][$weekNumber] ?? ['count' => 0, 'total' => 0.0];

            // Increment the number of withdraws for the user in this week
            $userHistory[$userId][$weekNumber]['count']++;

            $userWeekHistory = $userHistory[$userId][$weekNumber];

            // Convert the amount to EUR
            $convertedAmount = $currency === 'EUR'
                ? $amount
                : $this->currencyConverter->convert($amount, $currency, 'EUR');

            // Check if the user has reached the free withdraw limit for this week (3 withdraws and 1000 EUR)
            if ($userWeekHistory['count'] <= self::FREE_WITHDRAW_COUNT &&
                $userWeekHistory['total'] + $convertedAmount <= self::FREE_WITHDRAW_LIMIT) {
                
                $userHistory[$userId][$weekNumber]['total'] += $convertedAmount; 
                return "0.00";
            }
            
            // If the user exceeds the free withdraw limit or has exceeded the free withdraw count
            if ($userWeekHistory['total'] >= self::FREE_WITHDRAW_LIMIT || 
                $userWeekHistory['count'] > self::FREE_WITHDRAW_COUNT) {

                $excessAmount = $amount;
            } else {

                // Calculate the amount that exceeds the free withdraw limit
                $oldTotalAmount = $userWeekHistory['total'] - self::FREE_WITHDRAW_LIMIT;
                $excessAmount =  $amount + $this->currencyConverter->convert($oldTotalAmount, 'EUR', $currency);
            }

            // Update the user's history with the new total amount in EUR
            $userHistory[$userId][$weekNumber]['total'] += $convertedAmount; 

            // Calculate the fee in the original currency
            $fee = $excessAmount * self::PRIVATE_WITHDRAW_FEE_PERCENTAGE / 100;
            
            if ($currency === 'JPY') {
                return $this->formatOutput(ceil($fee), $currency); // JPY has no decimal places
            }

            return $this->formatOutput(ceil($fee * 100) / 100, $currency); 
        }

        return "0.00";
    }

    private function validateInputData(array $operation): void
    {
        [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;

        if (!$this->isValidDate($date)) {
            throw new \Exception("Invalid date format: $date");
        }

        if (!is_numeric($userId) || $userId <= 0) {
            throw new \Exception("Invalid user ID: $userId");
        }

        if (!in_array($userType, ['private', 'business'], true)) {
            throw new \Exception("Invalid user type: $userType");
        }

        if (!in_array($operationType, ['deposit', 'withdraw'], true)) {
            throw new \Exception("Invalid operation type: $operationType");
        }

        if (!is_numeric($amount) || $amount <= 0) {
            throw new \Exception("Invalid amount: $amount");
        }

        if (!is_string($currency) || strlen($currency) !== 3) {
            throw new \Exception("Invalid currency: $currency");
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}