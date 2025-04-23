<?php

namespace App\Service;

use App\Entity\Transaction;

class UserHistoryManager
{
    private $userHistory = [];
    private $userId;
    private $weekNumber;

    // This method initializes the user transaction history for a given transaction.
    // It sets the user ID and week number based on the transaction date.
    // Init need to be called first before any other method to ensure the user history is set up correctly.
    // It also initializes the user history for the week if it doesn't exist.

    public function initUserTransaction(Transaction $transaction): void
    {
        $this->userId = $transaction->getUserId();

        $this->weekNumber = (new \DateTime($transaction->getDate()))->format('oW');

        if (!isset($this->userHistory[$this->userId][$this->weekNumber])) {
            $this->userHistory[$this->userId][$this->weekNumber] = ['count' => 0, 'total' => 0.0];
        }

        // Increment the withdrawal count for the user in the current week
        $this->userHistory[$this->userId][$this->weekNumber]['count']++;
    }


    public function getUserWeekHistory(): array
    {
        $this->ensureUserTransactionInitialized();

        // Return the user's transaction history for the current week
        return $this->userHistory[$this->userId][$this->weekNumber];
    }


    public function addUserTotal(float $amount): void
    {
        $this->ensureUserTransactionInitialized();

        // Add the amount to the user's total for the current week
        $this->userHistory[$this->userId][$this->weekNumber]['total'] += $amount;
    }

    private function ensureUserTransactionInitialized(): void
    {
        if (!isset($this->userHistory[$this->userId][$this->weekNumber])) {
            throw new \Exception("User history not initialized. Call initUserTransaction first.");
        }
    }
}