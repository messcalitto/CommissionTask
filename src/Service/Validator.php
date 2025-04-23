<?php

namespace App\Service;
use App\Config\TransactionType;
use App\Config\UserType;

class Validator
{
    public function validateOperation(array $operation): void
    {
        [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;

        if (!$this->isValidDate($date)) {
            throw new \Exception("Invalid date format: $date");
        }

        if (!is_numeric($userId) || $userId <= 0) {
            throw new \Exception("Invalid user ID: $userId");
        }

        if (!in_array($userType, [UserType::PRIVATE, UserType::BUSINESS], true)) {
            throw new \Exception("Invalid user type: $userType");
        }

        if (!in_array($operationType, [TransactionType::DEPOSIT, TransactionType::WITHDRAW], true)) {
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