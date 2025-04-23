<?php

namespace App\Config;

class Config
{
    private array $config;

    public function __construct(array $env = null)
    {
        // Use provided environment or fallback to $_ENV
        $this->config = $env ?? $_ENV;
        
    }
 

    // Typed getters for specific configuration values
    
    public function getDepositFeePercentage(): string
    {
        return $this->config['DEPOSIT_FEE_PERCENTAGE'];
    }

    public function getPrivateWithdrawFeePercentage(): string
    {
        return $this->config['PRIVATE_WITHDRAW_FEE_PERCENTAGE'];
    }

    public function getBusinessWithdrawFeePercentage(): string
    {
        return $this->config['BUSINESS_WITHDRAW_FEE_PERCENTAGE'];
    }

    public function getFreeWithdrawLimit(): string
    {
        return $this->config['FREE_WITHDRAW_LIMIT'];
    }

    public function getFreeWithdrawCount(): int
    {
        return (int)$this->config['FREE_WITHDRAW_COUNT'];
    }

    public function getExchangeRatesApiUrl(): string
    {
        return $this->config['EXCHANGE_RATES_API_URL'] . '?access_key=' . $this->config['EXCHANGE_RATES_API_KEY'];
    }

    public function isDevelopmentMode(): bool
    {
        return $this->config['APP_ENV'] === 'development';
    }
    
    public function getTestExchangeRates(): array
    {
        return [
            'USD' => 1.1497,
            'JPY' => 129.53,
            'EUR' => 1.0
        ];
    }

    public function getBaseCurrency(): string
    {
        return $this->config['BASE_CURRENCY'];
    }
   
    
}
