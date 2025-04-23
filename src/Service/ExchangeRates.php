<?php

namespace App\Service;

class ExchangeRates
{
    private string $exchangeRatesUrl;
    private $config;

    public function __construct(string $exchangeRatesUrl, $config)
    {
        $this->config = $config;
        $this->exchangeRatesUrl = $exchangeRatesUrl;
    }

    
    public function getExchangeRates(): array
    {
        if ($this->config->isDevelopmentMode()) {
            return $this->config->getTestExchangeRates();
        }
        
        $response = @file_get_contents($this->exchangeRatesUrl);

        if ($response === false) {
            throw new ExchangeRatesException("Failed to fetch exchange rates. The server may be unavailable.");
        }

        $data = json_decode($response, true);

        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new ExchangeRatesException("Invalid response from exchange rates API. Check your API key.");
        }

        $exchangeRates = $data['rates'];
        $exchangeRates[$this->config->getBaseCurrency()] = 1.0; // Ensure BASE_CURRENCY is included
        return $exchangeRates;
    }
}

class ExchangeRatesException extends \Exception
{
}
