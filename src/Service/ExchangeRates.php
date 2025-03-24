<?php

namespace App\Service;

class ExchangeRates
{
    private array $exchangeRates;

    public function __construct(string $apiKey)
    {
        $exchangeRatesUrl = "https://api.exchangeratesapi.io/latest?access_key=" . $apiKey;
        $response = @file_get_contents($exchangeRatesUrl);

        if ($response === false) {
            throw new \Exception("Failed to fetch exchange rates. The server may be unavailable.");
        }

        $data = json_decode($response, true);

        if (!isset($data['rates']) || !is_array($data['rates'])) {
            throw new \Exception("Invalid response from exchange rates API. Check your API key.");
        }

        $this->exchangeRates = $data['rates'];
        $this->exchangeRates['EUR'] = 1.0; // Ensure EUR is included
    }

    public function getRates(): array
    {
        return $this->exchangeRates;
    }
}
