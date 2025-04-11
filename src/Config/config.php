<?php
namespace App\Config;


class Config
{
    public static function get(string $key, $default = null) :string
    {
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Get an environment variable as a specific type
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) $_ENV[$key] ?? $default;
    }
    
    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $_ENV[$key] ?? $default;
    }
    
    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) $_ENV[$key] ?? $default;
    }

    public static function getExchangeRatesApiUrl(): string
    {
        return $_ENV['EXCHANGE_RATES_API_URL']. $_ENV['EXCHANGE_RATES_API_KEY'];
    }
}