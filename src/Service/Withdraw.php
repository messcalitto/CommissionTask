<?php
namespace App\Service;

use App\Config\UserType;
use App\Config\Config;
use App\Entity\Transaction;
use App\Service\Math;
use App\Service\StrategyInterface;

class Withdraw implements StrategyInterface
{
    private $userHistoryManager;
    private $currencyConverter;
    private $math;
    private $config;

    public function __construct(CurrencyConverter $currencyConverter, UserHistoryManager $userHistoryManager, Math $math, Config $config)
    {
        $this->currencyConverter = $currencyConverter;
        $this->userHistoryManager = $userHistoryManager;
        $this->math = $math;
        $this->config = $config;
    }

    public function calculateFee(Transaction $transaction): float
    {
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transaction amount must be positive");
        }

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
        $fee = $this->math->mul($transaction->getAmount(), $this->config->getBusinessWithdrawFeePercentage());
        $fee = $this->math->div($fee, 100);
        return $this->math->roundUp($fee);
    }

    /**
     * Calculates the fee for a private user withdrawal
     * 
     * Private rules:
     * - First 3 withdrawals per week are free up to a total of 1000 EUR
     * - Any amount exceeding the free limit is charged at PRIVATE_WITHDRAW_FEE_PERCENTAGE
     * - After 3 withdrawals, all amounts are charged at PRIVATE_WITHDRAW_FEE_PERCENTAGE
     * 
     * @param Transaction $transaction The withdrawal transaction
     * @return float The calculated fee
     */

    private function calculateFeePrivate(Transaction $transaction): float
    {
        $this->userHistoryManager->initUserTransaction($transaction);

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();

        $userWeekHistory = $this->userHistoryManager->getUserWeekHistory();

        $convertedAmount = $currency === $this->config->getBaseCurrency()
            ? $amount
            : $this->currencyConverter->convert($amount, $currency, $this->config->getBaseCurrency());

        // Update the user's history with the new total amount in EUR
        $this->userHistoryManager->addUserTotal($convertedAmount); 

         // Check if the user has free withdraw access for this week (3 withdraws up to 1000 EUR)
        if ($this->isFreeWithdraw($userWeekHistory, $convertedAmount)) {
            
            return 0.0; // No fee for free withdraws

        }   // If the user exceeds the free withdraw amount limit or has exceeded the free withdraw count
        elseif ( $this->isFreeWithdrawLimitExceeded($userWeekHistory) ) { 
            $excessAmount = $amount;
        }
        else {
            // If the user has already exceeded the free withdraw limit, calculate the excess amount
            $excessAmount =  $this->calculateExcessAmount($userWeekHistory, $amount, $currency);
        }

        // Calculate the fee in the original currency
        return $this->calculateFeeForAmount($excessAmount, $currency);
    }


    private function isFreeWithdraw($userWeekHistory, float $convertedAmount): bool
    {
        return $userWeekHistory['count'] <= $this->config->getFreeWithdrawCount() &&
        $this->math->add($userWeekHistory['total'], $convertedAmount) <= $this->config->getFreeWithdrawLimit();
    }


    private function isFreeWithdrawLimitExceeded($userWeekHistory): bool
    {
        return $userWeekHistory['total'] >= $this->config->getFreeWithdrawLimit() || 
        $userWeekHistory['count'] > $this->config->getFreeWithdrawCount();
    }


    private function calculateExcessAmount($userWeekHistory, float $amount, string $currency): float
    {
        $oldTotalAmount = $this->math->sub($userWeekHistory['total'], $this->config->getFreeWithdrawLimit());
        $oldTotalAmount = $this->currencyConverter->convert($oldTotalAmount, $this->config->getBaseCurrency(), $currency);
        return $this->math->add($amount, $oldTotalAmount);
    }


    private function calculateFeeForAmount(float $excessAmount, string $currency): float
    {
        $fee = $this->math->mul($excessAmount, $this->config->getPrivateWithdrawFeePercentage());
        $fee = $this->math->div($fee, 100);
        return $this->math->roundUp($fee, 2, $currency);
    }
}
