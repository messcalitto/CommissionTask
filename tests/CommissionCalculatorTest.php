<?php

namespace CommissionTask\Tests;

use PHPUnit\Framework\TestCase;
use App\Service\CsvReader;
use App\Service\CommissionCalculator;
use App\Service\CurrencyConverter;
use App\Service\ExchangeRates;

class CommissionCalculatorTest extends TestCase
{
    public function testCommissionCalculation()
    {
        // Mock exchange rates
        $exchangeRates = [
            'EUR' => 1.0,
            'USD' => 1.1497,
            'JPY' => 129.53,
        ];

        // Initialize services
        $csvReader = new CsvReader();
        $currencyConverter = new CurrencyConverter($exchangeRates);
        $commissionCalculator = new CommissionCalculator($currencyConverter);

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
            "0.00",
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
        $operations = $csvReader->read($inputFile);

        // Process operations
        $userHistory = [];
        $actualOutput = [];
        foreach ($operations as $operation) {
            $fee = $commissionCalculator->calculate($operation, $userHistory);
            $actualOutput[] = $fee;
        }

        // Assert output matches expected
        $this->assertEquals($expectedOutput, $actualOutput);

        // Clean up
        unlink($inputFile);
    }
}
