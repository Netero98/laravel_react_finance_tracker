<?php

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

test('getExchangeRates returns cached rates if available', function () {
    // Mock the cache to return predefined rates
    $mockRates = ['USD' => 1, 'EUR' => 0.11];
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn($mockRates);

    $service = new ExchangeRateService();
    $rates = $service->getExchangeRates();

    expect($rates['USD'])->toBe(1);
    expect($rates['EUR'])->toBe(0.11);
});

test('getExchangeRates fetches from API when cache is empty', function () {
    // Mock the API response
    $apiResponse = [
        'conversion_rates' => [
            'USD' => 1,
            'EUR' => 0.11,
            'GBP' => 0.22
        ]
    ];

    Http::fake([
        'https://v6.exchangerate-api.com/v6/*' => Http::response(json_encode($apiResponse), 200),
    ]);

    // Mock the cache to execute the callback
    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    // Mock the cache lock
    $mockLock = Mockery::mock('lock');
    $mockLock->shouldReceive('block')
        ->once()
        ->andReturnUsing(function ($seconds, $callback) {
            return $callback();
        });

    Cache::shouldReceive('lock')
        ->once()
        ->andReturn($mockLock);

    // Set a mock API key
    Config::set('services.exchange_rate_api_key', 'test-api-key');

    $service = new ExchangeRateService();
    $rates = $service->getExchangeRates();

    expect($rates)->toBe($apiResponse['conversion_rates']);
    expect($rates['USD'])->toBe(1);
    expect($rates['EUR'])->toBe(0.11);
    expect($rates['GBP'])->toBe(0.22);
});

test('getExchangeRates returns fallback rates when API fails', function () {
    // Mock the API to fail
    Http::fake([
        'https://v6.exchangerate-api.com/v6/*' => Http::response('', 500),
    ]);

    // Mock the cache to throw an exception
    Cache::shouldReceive('remember')
        ->once()
        ->andThrow(new Exception('API failed'));

    $service = new ExchangeRateService();
    $rates = $service->getExchangeRates();

    // Verify we got fallback rates
    expect($rates)->toBeArray();
    expect($rates['USD'])->toBe(1);
    expect($rates)->toHaveKey('EUR');
    expect($rates)->toHaveKey('GBP');
});

test('USD constant is defined correctly', function () {
    expect(ExchangeRateService::USD)->toBe('USD');
});

test('ALL_EXTERNAL_CURRENCIES contains major currencies', function () {
    $majorCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF'];

    foreach ($majorCurrencies as $currency) {
        expect(ExchangeRateService::ALL_EXTERNAL_CURRENCIES)->toContain($currency);
    }

    expect(count(ExchangeRateService::ALL_EXTERNAL_CURRENCIES))->toBeGreaterThan(100);
});
