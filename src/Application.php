<?php

namespace App;


use App\Service\CsvReader;
use App\Service\CommissionCalculator;
use App\Entity\Transaction;
use App\Service\Validator;



class Application
{ 
    private $csvReader;
    private $validator;
    private $commissionCalculator;

    public function __construct(
        CsvReader $csvReader,
        Validator $validator,
        CommissionCalculator $commissionCalculator
    ) {
        $this->csvReader = $csvReader;
        $this->validator = $validator;
        $this->commissionCalculator = $commissionCalculator;
    }
    
    /**
     * Main method to run the application.
     *
     * @param array $argv Command line arguments.
     */

    public function run(array $argv): void
    {
        $inputFile = $this->parseCommandLineArguments($argv);
        
        $operations = $this->readOperationsFromFile($inputFile);
        
        $this->validateOperations($operations);

        $transactions = $this->createTransactionsFromOperations($operations);
        
        $fees = $this->calculateFees($transactions);
        
        $this->outputResults($fees);
    }

    /**
     * Parses command line arguments and returns the input file path.
     *
     * @param array $argv Command line arguments.
     * @return string Input file path.
     */

    private function parseCommandLineArguments(array $argv): string
    {
        if (count($argv) < 2) {
            echo "Usage: php script.php input.csv\n";
            exit(1);
        }
        return $argv[1];
    }

    /**
     * Reads operations from the input file.
     *
     * @param string $inputFile Path to the input file.
     * @return array Array of operations.
     */

    private function readOperationsFromFile(string $inputFile): array
    {
        return $this->csvReader->read($inputFile);
    }

    /**
     * Creates Transaction objects from the operations array.
     *
     * @param array $operations Array of operations.
     * @return array Array of Transaction objects.
     */

    private function createTransactionsFromOperations(array $operations): array
    {
        $transactions = [];

        foreach ($operations as $operation) {
            
            [$date, $userId, $userType, $operationType, $amount, $currency] = $operation;

            $transactions[] = new Transaction($date, $userId, $userType, $operationType, $amount, $currency);
        }

        return $transactions;
    }

    /**
     * Validates the operations array.
     *
     * @param array $operations Array of operations.
     * @throws \Exception If validation fails.
     */

    private function validateOperations(array $operations): void
    {
        foreach ($operations as $operation) {
            $this->validator->validateOperation($operation);
        }
    }

    /**
     * Calculates fees for the transactions.
     *
     * @param array $transactions Array of Transaction objects.
     * @return array Array of calculated fees.
     */

    private function calculateFees(array $transactions): array
    {
        return $this->commissionCalculator->calculate($transactions);
    }

    /**
     * Outputs the results to the console.
     *
     * @param array $fees Array of calculated fees.
     */
    
    private function outputResults(array $fees): void
    {
        foreach ($fees as $fee) {
            echo $fee . PHP_EOL;
        }
    }
    
}
