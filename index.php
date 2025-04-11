<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';


use App\Service\CsvReader;
use App\Service\CommissionCalculator;
use App\Service\ExchangeRates;
use App\Entity\Transaction;
use App\Service\CurrencyConverter;
use App\Service\Validator;
use App\Config\Config;

try {
    
    if ($argc < 2) {
        echo "Usage: php script.php input.csv\n";
        exit(1);
    }

    $inputFile = $argv[1];

    $csvReader = new CsvReader();
    $operations = $csvReader->read($inputFile);
    
    $validator = new Validator();

    foreach ($operations as $operation) {
        
        $validator->validateOperation($operation);

        [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;

        $transactions[] = new Transaction($date, $userId, $userType, $operationType, $amount, $currency);
    }
    
    $exchangeRatesService = new ExchangeRates(Config::getExchangeRatesApiUrl());
    $exchangeRates = $exchangeRatesService->getRates();

    // For testing purposes
    $exchangeRates['USD'] = 1.1497;
    $exchangeRates['JPY'] = 129.53;

    $currencyConverter = new CurrencyConverter($exchangeRates);
    $commissionCalculator = new CommissionCalculator($currencyConverter);
    $fees = $commissionCalculator->calculate($transactions);
    
    foreach ($fees as $fee) {
        echo $fee . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
