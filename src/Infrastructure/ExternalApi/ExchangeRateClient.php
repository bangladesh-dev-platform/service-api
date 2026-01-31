<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

/**
 * Exchange Rate API Client
 * Uses exchangerate-api.com free tier or falls back to cached rates
 */
class ExchangeRateClient
{
    private const BASE_URL = 'https://open.er-api.com/v6/latest/BDT';
    
    private int $timeout;
    private ?string $cacheFile;

    public function __construct(int $timeout = 10, ?string $cacheDir = null)
    {
        $this->timeout = $timeout;
        $this->cacheFile = $cacheDir ? $cacheDir . '/exchange_rates.json' : null;
    }

    /**
     * Get exchange rates for BDT
     */
    public function getRates(): array
    {
        try {
            return $this->fetchLiveRates();
        } catch (\Throwable $e) {
            // Fall back to cached or default rates
            return $this->getCachedOrDefaultRates();
        }
    }

    /**
     * Fetch live rates from API
     */
    private function fetchLiveRates(): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => 'Accept: application/json'
            ]
        ]);

        $response = @file_get_contents(self::BASE_URL, false, $context);
        
        if ($response === false) {
            throw new \RuntimeException('Failed to fetch exchange rates');
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['rates'])) {
            throw new \RuntimeException('Invalid exchange rate response');
        }

        $transformed = $this->transformResponse($data);
        
        // Cache the rates
        $this->cacheRates($transformed);
        
        return $transformed;
    }

    /**
     * Transform API response to our format
     */
    private function transformResponse(array $data): array
    {
        $rates = $data['rates'] ?? [];
        
        // We need rates FROM other currencies TO BDT
        // The API gives us BDT to other currencies, so we invert
        $targetCurrencies = ['USD', 'EUR', 'GBP', 'SAR', 'AED', 'INR', 'MYR', 'SGD'];
        
        $bdtRates = [];
        foreach ($targetCurrencies as $currency) {
            if (isset($rates[$currency]) && $rates[$currency] > 0) {
                // Invert: 1 USD = X BDT
                $bdtRates[$currency] = round(1 / $rates[$currency], 2);
            }
        }

        return [
            'base' => 'BDT',
            'rates' => $this->formatRates($bdtRates),
            'updated_at' => date('c'),
            'source' => 'live',
        ];
    }

    /**
     * Format rates with labels
     */
    private function formatRates(array $rates): array
    {
        $labels = [
            'USD' => ['name' => 'US Dollar', 'name_bn' => 'মার্কিন ডলার', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'name_bn' => 'ইউরো', 'symbol' => '€'],
            'GBP' => ['name' => 'British Pound', 'name_bn' => 'ব্রিটিশ পাউন্ড', 'symbol' => '£'],
            'SAR' => ['name' => 'Saudi Riyal', 'name_bn' => 'সৌদি রিয়াল', 'symbol' => '﷼'],
            'AED' => ['name' => 'UAE Dirham', 'name_bn' => 'ইউএই দিরহাম', 'symbol' => 'د.إ'],
            'INR' => ['name' => 'Indian Rupee', 'name_bn' => 'ভারতীয় রুপি', 'symbol' => '₹'],
            'MYR' => ['name' => 'Malaysian Ringgit', 'name_bn' => 'মালয়েশিয়ান রিংগিত', 'symbol' => 'RM'],
            'SGD' => ['name' => 'Singapore Dollar', 'name_bn' => 'সিঙ্গাপুর ডলার', 'symbol' => 'S$'],
        ];

        $formatted = [];
        foreach ($rates as $code => $rate) {
            $label = $labels[$code] ?? ['name' => $code, 'name_bn' => $code, 'symbol' => ''];
            $formatted[] = [
                'code' => $code,
                'name' => $label['name'],
                'name_bn' => $label['name_bn'],
                'symbol' => $label['symbol'],
                'rate' => $rate,
                'rate_formatted' => '৳' . number_format($rate, 2),
            ];
        }

        return $formatted;
    }

    /**
     * Cache rates to file
     */
    private function cacheRates(array $rates): void
    {
        if ($this->cacheFile) {
            @file_put_contents($this->cacheFile, json_encode($rates));
        }
    }

    /**
     * Get cached or default rates
     */
    private function getCachedOrDefaultRates(): array
    {
        // Try to read from cache
        if ($this->cacheFile && file_exists($this->cacheFile)) {
            $cached = @file_get_contents($this->cacheFile);
            if ($cached) {
                $data = json_decode($cached, true);
                if ($data) {
                    $data['source'] = 'cached';
                    return $data;
                }
            }
        }

        // Return default rates (approximate values as of 2025)
        return [
            'base' => 'BDT',
            'rates' => $this->formatRates([
                'USD' => 110.50,
                'EUR' => 120.25,
                'GBP' => 140.80,
                'SAR' => 29.45,
                'AED' => 30.10,
                'INR' => 1.32,
                'MYR' => 24.50,
                'SGD' => 82.30,
            ]),
            'updated_at' => date('c'),
            'source' => 'default',
        ];
    }
}
