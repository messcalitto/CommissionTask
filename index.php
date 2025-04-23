<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';

use App\Application;
use App\Service\CsvReader;
use App\Service\Validator;
use App\Service\ExchangeRates;
use App\Service\CommissionCalculator;
use App\Service\CurrencyConverter;
use App\Service\Deposit;
use App\Service\Withdraw;
use App\Service\UserHistoryManager;
use App\Config\Config;
use App\Service\Formatter;
use App\Service\Math;
use App\Config\TransactionType;

try {
    
    $config = new Config($_ENV);
    
    $math = new Math($config);
    $userHistoryManager = new UserHistoryManager();
    $exchangeRates = new ExchangeRates($config->getExchangeRatesApiUrl(), $config);
    $currencyConverter = new CurrencyConverter($exchangeRates->getExchangeRates());
     
    // Initialize the calculator
    $commissionCalculator = new CommissionCalculator(new Formatter());
    
    // Add withdraw strategy
    $commissionCalculator->addStrategy(
        TransactionType::WITHDRAW, 
        new Withdraw($currencyConverter, $userHistoryManager, $math, $config)
    );
    
    // Add deposit strategy
    $commissionCalculator->addStrategy(
        TransactionType::DEPOSIT, 
        new Deposit($math, $config)
    );
    

    $app = new Application(
        new CsvReader(),
        new Validator(),
        $commissionCalculator
    );

    $app->run($argv);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}