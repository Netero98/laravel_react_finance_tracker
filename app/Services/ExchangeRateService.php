<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates';

    public const CACHE_TTL = 7200;

    public const ALL_EXTERNAL_CURRENCIES = [
        'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
        'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD',
        'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC',
        'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR',
        'FJD', 'FKP', 'FOK', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ',
        'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD',
        'IRR', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KID', 'KMF',
        'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD',
        'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRU', 'MUR', 'MVR', 'MWK', 'MXN',
        'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN',
        'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR',
        'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLE', 'SLL', 'SOS', 'SRD', 'SSP',
        'STN', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TVD',
        'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VES', 'VND', 'VUV', 'WST', 'XAF',
        'XCD', 'XCG', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWL'
    ];

    public const USD = 'USD';

    /**
     * Get exchange rates with caching
     *
     * @return array
     */
    public function getExchangeRates(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addSeconds(self::CACHE_TTL), function () {
                $lock = Cache::lock(self::CACHE_KEY . '-lock', 10); // 10 секунд блокировки

                return $lock->block(5, function () {
                    Log::info('Fetching exchange rates from API');
                    return $this->fetchExchangeRates();
                });
            });
        } catch (\Exception $e) {
            if (!app()->environment(['local', 'testing'])) {
                Log::error('Exception in getExchangeRates: ' . $e->getMessage());
            }

            return $this->getFallbackRates();
        }
    }

    /**
     * Fetch exchange rates from the API
     *
     * @return array
     *
     * @throws Throwable
     */
    private function fetchExchangeRates(): array
    {
        $api_key = config('services.exchange_rate_api_key');

        $url = 'https://v6.exchangerate-api.com/v6/' . $api_key . '/latest/USD';

        $response = Http::get($url);

        if ($response->successful()) {
             return json_decode($response->getBody()->getContents(), true)['conversion_rates'];
        }

        throw new Exception('Failed to fetch exchange rates from external API');
    }

    private function getFallbackRates(): array
    {
        return [
            "USD" => 1,
            "AED" => 3.6725,
            "AFN" => 69.9467,
            "ALL" => 86.6851,
            "AMD" => 384.9543,
            "ANG" => 1.79,
            "AOA" => 922.3848,
            "ARS" => 1145.0,
            "AUD" => 1.5527,
            "AWG" => 1.79,
            "AZN" => 1.7002,
            "BAM" => 1.7271,
            "BBD" => 2.0,
            "BDT" => 121.8053,
            "BGN" => 1.7272,
            "BHD" => 0.376,
            "BIF" => 2978.8802,
            "BMD" => 1.0,
            "BND" => 1.2895,
            "BOB" => 6.9311,
            "BRL" => 5.6673,
            "BSD" => 1.0,
            "BTN" => 85.6382,
            "BWP" => 13.5002,
            "BYN" => 3.04,
            "BZD" => 2.0,
            "CAD" => 1.3863,
            "CDF" => 2891.6291,
            "CHF" => 0.8255,
            "CLP" => 944.0411,
            "CNY" => 7.2025,
            "COP" => 4183.8695,
            "CRC" => 507.3934,
            "CUP" => 24.0,
            "CVE" => 97.3719,
            "CZK" => 21.9754,
            "DJF" => 177.721,
            "DKK" => 6.5896,
            "DOP" => 59.0153,
            "DZD" => 132.7721,
            "EGP" => 49.8603,
            "ERN" => 15.0,
            "ETB" => 134.0882,
            "EUR" => 0.883,
            "FJD" => 2.2651,
            "FKP" => 0.7452,
            "FOK" => 6.5893,
            "GBP" => 0.7452,
            "GEL" => 2.7391,
            "GGP" => 0.7452,
            "GHS" => 12.0129,
            "GIP" => 0.7452,
            "GMD" => 72.7986,
            "GNF" => 8698.9771,
            "GTQ" => 7.6852,
            "GYD" => 210.1851,
            "HKD" => 7.8307,
            "HNL" => 26.0517,
            "HRK" => 6.6535,
            "HTG" => 131.0123,
            "HUF" => 355.3755,
            "IDR" => 16396.1701,
            "ILS" => 3.5548,
            "IMP" => 0.7452,
            "INR" => 85.6383,
            "IQD" => 1312.3946,
            "IRR" => 42071.415,
            "ISK" => 127.7552,
            "JEP" => 0.7452,
            "JMD" => 159.0887,
            "JOD" => 0.709,
            "JPY" => 143.76,
            "KES" => 129.1819,
            "KGS" => 87.4454,
            "KHR" => 4003.0585,
            "KID" => 1.5529,
            "KMF" => 434.4427,
            "KRW" => 1377.4951,
            "KWD" => 0.307,
            "KYD" => 0.8333,
            "KZT" => 509.8738,
            "LAK" => 21714.2543,
            "LBP" => 89500.0,
            "LKR" => 299.7289,
            "LRD" => 200.3254,
            "LSL" => 17.9506,
            "LYD" => 5.4904,
            "MAD" => 9.2229,
            "MDL" => 17.3735,
            "MGA" => 4506.5861,
            "MKD" => 54.5741,
            "MMK" => 2102.7831,
            "MNT" => 3563.104,
            "MOP" => 8.0658,
            "MRU" => 39.8934,
            "MUR" => 45.4104,
            "MVR" => 15.4802,
            "MWK" => 1741.1913,
            "MXN" => 19.3546,
            "MYR" => 4.2709,
            "MZN" => 63.9338,
            "NAD" => 17.9506,
            "NGN" => 1586.1595,
            "NIO" => 36.8446,
            "NOK" => 10.1683,
            "NPR" => 137.0211,
            "NZD" => 1.6845,
            "OMR" => 0.3845,
            "PAB" => 1.0,
            "PEN" => 3.6812,
            "PGK" => 4.1532,
            "PHP" => 55.6633,
            "PKR" => 281.9998,
            "PLN" => 3.7467,
            "PYG" => 8014.8853,
            "QAR" => 3.64,
            "RON" => 4.476,
            "RSD" => 103.4014,
            "RUB" => 79.949,
            "RWF" => 1442.8402,
            "SAR" => 3.75,
            "SBD" => 8.5546,
            "SCR" => 14.396,
            "SDG" => 545.0939,
            "SEK" => 9.5762,
            "SGD" => 1.2895,
            "SHP" => 0.7452,
            "SLE" => 22.8207,
            "SLL" => 22820.7227,
            "SOS" => 572.2545,
            "SRD" => 36.6939,
            "SSP" => 4556.9032,
            "STN" => 21.6353,
            "SYP" => 12956.9529,
            "SZL" => 17.9506,
            "THB" => 32.7274,
            "TJS" => 10.3381,
            "TMT" => 3.5059,
            "TND" => 2.9915,
            "TOP" => 2.3731,
            "TRY" => 38.8719,
            "TTD" => 6.8016,
            "TVD" => 1.5529,
            "TWD" => 29.9495,
            "TZS" => 2688.722,
            "UAH" => 41.4396,
            "UGX" => 3656.2688,
            "UYU" => 41.7192,
            "UZS" => 12914.0722,
            "VES" => 94.9652,
            "VND" => 25986.3001,
            "VUV" => 121.1913,
            "WST" => 2.7027,
            "XAF" => 579.257,
            "XCD" => 2.7,
            "XCG" => 1.79,
            "XDR" => 0.7365,
            "XOF" => 579.257,
            "XPF" => 105.3787,
            "YER" => 244.2366,
            "ZAR" => 17.9508,
            "ZMW" => 27.2099,
            "ZWL" => 26.8853
        ];
    }
}
