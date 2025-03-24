<?php

namespace App\Service;

class CsvReader
{
    public function read(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("Unable to read the file: $filePath");
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        } else {
            throw new \Exception("Failed to open the file: $filePath");
        }

        return $rows;
    }
}