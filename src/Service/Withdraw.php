<?php
namespace App\Service;

use App\Config\Config;
use App\Config\UserType;
use App\Entity\Transaction;
use App\Service\Math;


class Withdraw extends Strategy
{
    protected $userHistory = [];

    public function calculateFee(Transaction $transaction): float
    {
        
        $userType = $transaction->getUserType();

        switch ($userType) {
            case UserType::BUSINESS:
                return $this->calculateFeeBusiness($transaction);
            case UserType::PRIVATE:
                return $this->calculateFeePrivate($transaction);
            default:
                return 0.0;
        }
    }

    private function calculateFeeBusiness(Transaction $transaction): float
    {
        return Math::roundUp($transaction->getAmount() * Config::getFloat('BUSINESS_WITHDRAW_FEE_PERCENTAGE') / 100);
    }


    private function calculateFeePrivate(Transaction $transaction): float
    {
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $userId = $transaction->getUserId();
        $date = $transaction->getDate();

        $weekNumber = (new \DateTime($date))->format('oW');
        $this->userHistory[$userId][$weekNumber] = $this->userHistory[$userId][$weekNumber] ?? ['count' => 0, 'total' => 0.0];
        $this->userHistory[$userId][$weekNumber]['count']++;

        $userWeekHistory = $this->userHistory[$userId][$weekNumber];

        $convertedAmount = $currency === 'EUR'
            ? $amount
            : $this->currencyConverter->convert($amount, $currency, 'EUR');

         // Check if the user has reached the free withdraw limit for this week (3 withdraws and 1000 EUR)
        if ($userWeekHistory['count'] <= Config::getInt('FREE_WITHDRAW_COUNT') &&
            $userWeekHistory['total'] + $convertedAmount <= Config::getInt('FREE_WITHDRAW_LIMIT')) {
            
            $this->userHistory[$userId][$weekNumber]['total'] += $convertedAmount; 
            return 0.00;
        }
        
        // If the user exceeds the free withdraw limit or has exceeded the free withdraw count
        if ($userWeekHistory['total'] >= Config::getInt('FREE_WITHDRAW_LIMIT') || 
            $userWeekHistory['count'] > Config::getInt('FREE_WITHDRAW_COUNT')) {

            $excessAmount = $amount;
        } else {

            // Calculate the amount that exceeds the free withdraw limit
            $oldTotalAmount = $userWeekHistory['total'] - Config::getInt('FREE_WITHDRAW_LIMIT');
            $excessAmount =  $amount + $this->currencyConverter->convert($oldTotalAmount, 'EUR', $currency);
        }

        // Update the user's history with the new total amount in EUR
        $this->userHistory[$userId][$weekNumber]['total'] += $convertedAmount; 

        // Calculate the fee in the original currency
        return Math::roundUp($excessAmount * Config::getFloat('PRIVATE_WITHDRAW_FEE_PERCENTAGE') / 100, 2, $currency);
    }
}
