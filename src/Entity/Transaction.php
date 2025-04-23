<?php

namespace App\Entity;

class Transaction
{
    private $userId;
    private $userType;
    private $operationType;
    private $amount;
    private $currency;
    private $date;

    public function __construct(
        string $date,
        int $userId,
        string $userType,
        string $operationType,
        float $amount,
        string $currency
    ) {
        $this->userId = $userId;
        $this->userType = $userType;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->date = $date;
    }

    public function getUserId() { return $this->userId; }
    public function getUserType() { return $this->userType; }
    public function getOperationType() { return $this->operationType; }
    public function getAmount() { return $this->amount; }
    public function getCurrency() { return $this->currency; }
    public function getDate() { return $this->date; }
}