<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';

use App\Service\CsvReader;
use App\Service\CommissionCalculator;
use App\Service\CurrencyConverter;
use App\Service\ExchangeRates;

try {

    // Initialize exchange rates
    $exchangeRatesService = new ExchangeRates(EXCHANGE_RATES_API_KEY);
    $exchangeRates = $exchangeRatesService->getRates();

    // For test purposes
    // $exchangeRates['USD'] = 1.1497;
    // $exchangeRates['JPY'] = 129.53;

    // Initialize services
    $csvReader = new CsvReader();
    $currencyConverter = new CurrencyConverter($exchangeRates);
    $commissionCalculator = new CommissionCalculator($currencyConverter);

    // Check if input file is provided
    if ($argc < 2) {
        echo "Usage: php script.php input.csv\n";
        exit(1);
    }

    // Read input CSV
    $inputFile = $argv[1];
    $operations = $csvReader->read($inputFile);

    // Process operations
    $userHistory = [];
    foreach ($operations as $operation) {
        $fee = $commissionCalculator->calculate($operation, $userHistory);
        echo $fee . PHP_EOL;
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
