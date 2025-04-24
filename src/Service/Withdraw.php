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

    /**
     * Calculates the fee for a withdrawal transaction based on user type
     * 
     * @param Transaction $transaction The withdrawal transaction
     * @return float The calculated fee
     */
    
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

    /**
     * Calculates the fee for a business user withdrawal
     * 
     * Business rules:
     * - All amounts are charged at BUSINESS_WITHDRAW_FEE_PERCENTAGE
     * 
     * @param Transaction $transaction The withdrawal transaction
     * @return float The calculated fee
     */

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
        // Initialize the user transaction history for the current transaction
        // This method should be called first to ensure the user history is set up correctly
        $this->userHistoryManager->initUserTransaction($transaction);

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();

        // Get the user's week history
        $userWeekHistory = $this->userHistoryManager->getUserWeekHistory();

        // Convert the amount to the base currency (EUR) for calculations
        // This is necessary because the fee calculation is based on the total amount in EUR
        $convertedAmount = $this->convertToBaseCurrency($amount, $currency);

        // Update the user's history with the new total amount in EUR
        $this->userHistoryManager->addUserTotal($convertedAmount); 

         // Check if the user has free withdraw access for this week (3 withdraws up to 1000 EUR)
        if ($this->isFreeWithdraw($userWeekHistory, $convertedAmount)) {
            return 0.0; // No fee for free withdraws
        }   
       
        // If the user exceeds the free withdraw amount limit or has exceeded the free withdraw count
        if ( $this->isFreeWithdrawLimitExceeded($userWeekHistory) ) { 
            $excessAmount = $amount;
        }
        else {
            // If the user has already exceeded the free withdraw limit, calculate the excess amount
            $excessAmount =  $this->calculateExcessAmount($userWeekHistory, $amount, $currency);
        }

        // Calculate the fee in the original currency
        return $this->calculateFeeForAmount($excessAmount, $currency);
    }

    /**
     * Converts the amount to the base currency (EUR) for calculations
     * 
     * @param float $amount The amount to convert
     * @param string $currency The currency of the amount
     * @return float The converted amount in base currency (EUR)
     **/

    private function convertToBaseCurrency(float $amount, string $currency): float
    {
        return $currency === $this->config->getBaseCurrency()
            ? $amount
            : $this->currencyConverter->convert($amount, $currency, $this->config->getBaseCurrency());
    }

    /**
     * Checks if the user is eligible for free withdrawals based on their history
     * 
     * @param array $userWeekHistory The user's transaction history for the current week
     * @param float $convertedAmount The converted amount in base currency (EUR)
     * @return bool True if the user is eligible for free withdrawals, false otherwise
     */

    private function isFreeWithdraw($userWeekHistory, float $convertedAmount): bool
    {
        return $userWeekHistory['count'] <= $this->config->getFreeWithdrawCount() &&
        $this->math->add($userWeekHistory['total'], $convertedAmount) <= $this->config->getFreeWithdrawLimit();
    }


    /**
     * Checks if the user has exceeded the free withdrawal limit
     * 
     * @param array $userWeekHistory The user's transaction history for the current week
     * @return bool True if the user has exceeded the free withdrawal limit, false otherwise
     */

    private function isFreeWithdrawLimitExceeded($userWeekHistory): bool
    {
        return $userWeekHistory['total'] >= $this->config->getFreeWithdrawLimit() || 
        $userWeekHistory['count'] > $this->config->getFreeWithdrawCount();
    }

    /**
     * Calculates the excess amount for fee calculation
     * 
     * @param array $userWeekHistory The user's transaction history for the current week
     * @param float $amount The amount to withdraw
     * @param string $currency The currency of the amount
     * @return float The excess amount for fee calculation
     */

    private function calculateExcessAmount($userWeekHistory, float $amount, string $currency): float
    {
        $oldTotalAmount = $this->math->sub($userWeekHistory['total'], $this->config->getFreeWithdrawLimit());
        $oldTotalAmount = $this->currencyConverter->convert($oldTotalAmount, $this->config->getBaseCurrency(), $currency);
        return $this->math->add($amount, $oldTotalAmount);
    }

    /**
     * Calculates the fee for the excess amount
     * 
     * @param float $excessAmount The excess amount for fee calculation
     * @param string $currency The currency of the excess amount
     * @return float The calculated fee for the excess amount
     */

    private function calculateFeeForAmount(float $excessAmount, string $currency): float
    {
        $fee = $this->math->mul($excessAmount, $this->config->getPrivateWithdrawFeePercentage());
        $fee = $this->math->div($fee, 100);
        return $this->math->roundUp($fee, 2, $currency);
    }
}
