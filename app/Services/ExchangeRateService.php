<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates';

    public const FRESH_TTL = 7200;
    public const STALE_TTL = 14400;

    /**
     * Get exchange rates with caching
     *
     * @return array
     */
    public function getExchangeRates(): array
    {
        return Cache::flexible(self::CACHE_KEY, [self::FRESH_TTL, self::STALE_TTL] , function () {
            Log::info('Fetching exchange rates from API');
            return $this->fetchExchangeRates();
        });
    }

    /**
     * Fetch exchange rates from the API
     *
     * @return array
     */
    private function fetchExchangeRates(): array
    {
        $api_key = config('services.exchange_rate_api_key');

        $url = 'https://v6.exchangerate-api.com/v6/' . $api_key . '/latest/USD';

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                return $response->json('rates', $this->getFallbackRates());
            }

            Log::error('Failed to fetch exchange rates', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getFallbackRates();
        } catch (\Exception $e) {
            Log::error('Exception when fetching exchange rates: ' . $e->getMessage());
            return $this->getFallbackRates();
        }
    }

    /**
     * Get fallback exchange rates in case the API call fails
     *
     * @return array
     */
    private function getFallbackRates(): array
    {
        return [
            'USD' => 1,
            'EUR' => 0.92,
            'GBP' => 0.78,
            'THB' => 35.5,
            'RUB' => 80.5,
            // Add more currencies as needed
        ];
    }
}
