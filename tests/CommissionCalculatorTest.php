<?php

namespace CommissionTask\Tests;

require_once __DIR__ . '/../src/Config/config.php';

use PHPUnit\Framework\TestCase;
use App\Service\CsvReader;
use App\Service\CommissionCalculator;
use App\Service\CurrencyConverter;
use App\Service\Validator;
use App\Config\Config;

class CommissionCalculatorTest extends TestCase
{
    /**
     * Test the index workflow of the commission calculator.
     *
     * This test simulates the entire process of reading a CSV file, validating transactions,
     * calculating commissions, and comparing the output with expected results.
     */

    public function testIndexWorkflow()
    {
        // Mock exchange rates
        $exchangeRates = [
            'EUR' => 1.0,
            'USD' => 1.1497,
            'JPY' => 129.53,
        ];

        // Input data (from the task prompt)
        $inputData = <<<CSV
2014-12-31,4,private,withdraw,1200.00,EUR
2015-01-01,4,private,withdraw,1000.00,EUR
2016-01-05,4,private,withdraw,1000.00,EUR
2016-01-05,1,private,deposit,200.00,EUR
2016-01-06,2,business,withdraw,300.00,EUR
2016-01-06,1,private,withdraw,30000,JPY
2016-01-07,1,private,withdraw,1000.00,EUR
2016-01-07,1,private,withdraw,100.00,USD
2016-01-10,1,private,withdraw,100.00,EUR
2016-01-10,2,business,deposit,10000.00,EUR
2016-01-10,3,private,withdraw,1000.00,EUR
2016-02-15,1,private,withdraw,300.00,EUR
2016-02-19,5,private,withdraw,3000000,JPY
CSV;

        // Expected output
        $expectedOutput = [
            "0.60",
            "3.00",
            "0.00",
            "0.06",
            "1.50",
            "0",
            "0.70",
            "0.30",
            "0.30",
            "3.00",
            "0.00",
            "0.00",
            "8612",
        ];

        $inputFile = sys_get_temp_dir() . '/test_input.csv';
        file_put_contents($inputFile, $inputData);


        // Initialize services
        $csvReader = new CsvReader();
        $operations = $csvReader->read($inputFile);

        $validator = new Validator();
        $transactions = [];

        foreach ($operations as $operation) {
            $validator->validateOperation($operation);
            [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;
            $transactions[] = new \App\Entity\Transaction($date, $userId, $userType, $operationType, $amount, $currency);
        }

        $currencyConverter = new CurrencyConverter($exchangeRates);
        $userHistoryManager = new \App\Service\UserHistoryManager();
        
        $config = new Config();
        
        $commissionCalculator = new CommissionCalculator(new \App\Service\Formatter());
        $commissionCalculator->addStrategy(\App\Config\TransactionType::WITHDRAW, new \App\Service\Withdraw($currencyConverter, $userHistoryManager, new \App\Service\Math($config), $config));
        $commissionCalculator->addStrategy(\App\Config\TransactionType::DEPOSIT, new \App\Service\Deposit(new \App\Service\Math($config), $config));

        $fees = $commissionCalculator->calculate($transactions);

        // Assert output matches expected
        $this->assertEquals($expectedOutput, $fees);

        // Clean up
        unlink($inputFile);
    }
}
