<?php

declare(strict_types=1);

namespace App\Infrastructure\ExchangeRate;

use App\Ports\Out\ExchangeRate\ExchangeRateProviderPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DolarApiAndBinanceRateProvider implements ExchangeRateProviderPort
{
    /**
     * @return array<int, array{source: string, from_currency: string, to_currency: string, quote_rate: string, rate: string}>
     */
    public function fetchRates(): array
    {
        $rates = [];

        // 1. Venezuela Rates: BCV and Paralelo via DolarAPI
        try {
            $response = Http::timeout(15)->retry(2, 500)->get('https://ve.dolarapi.com/v1/dolares');
            if ($response->successful()) {
                /** @var array<int, array<string, mixed>> $data */
                $data = $response->json();
                foreach ($data as $item) {
                    $fuente = (string) ($item['fuente'] ?? '');
                    $promedio = $item['promedio'] ?? null;

                    if (! is_numeric($promedio) || (float) $promedio <= 0) {
                        continue;
                    }

                    $quote = (string) $promedio;
                    $multiplier = bcdiv('1', $quote, 12);

                    if ($fuente === 'oficial') {
                        $rates[] = [
                            'source' => 'bcv',
                            'from_currency' => 'VES',
                            'to_currency' => 'USD',
                            'quote_rate' => $quote,
                            'rate' => $multiplier,
                        ];
                        // Default fallback for VES
                        $rates[] = [
                            'source' => 'default',
                            'from_currency' => 'VES',
                            'to_currency' => 'USD',
                            'quote_rate' => $quote,
                            'rate' => $multiplier,
                        ];
                    } elseif ($fuente === 'paralelo') {
                        $rates[] = [
                            'source' => 'paralelo',
                            'from_currency' => 'VES',
                            'to_currency' => 'USD',
                            'quote_rate' => $quote,
                            'rate' => $multiplier,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to fetch rates from DolarAPI: '.$e->getMessage());
        }

        // 2. Binance P2P: VES and COP
        try {
            // VES P2P
            $vesResponse = Http::timeout(10)->retry(2, 500)->post('https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search', [
                'asset' => 'USDT',
                'fiat' => 'VES',
                'merchantCheck' => false,
                'page' => 1,
                'rows' => 3,
                'payTypes' => [],
                'tradeType' => 'BUY',
            ]);

            if ($vesResponse->successful()) {
                $price = $vesResponse->json('data.0.adv.price');
                if (is_numeric($price) && (float) $price > 0) {
                    $quote = (string) $price;
                    $multiplier = bcdiv('1', $quote, 12);
                    $rates[] = [
                        'source' => 'binance',
                        'from_currency' => 'VES',
                        'to_currency' => 'USD',
                        'quote_rate' => $quote,
                        'rate' => $multiplier,
                    ];

                    $hasVesDefault = false;
                    foreach ($rates as $r) {
                        if ($r['source'] === 'default') {
                            $hasVesDefault = true;
                            break;
                        }
                    }

                    if (! $hasVesDefault) {
                        $rates[] = [
                            'source' => 'default',
                            'from_currency' => 'VES',
                            'to_currency' => 'USD',
                            'quote_rate' => $quote,
                            'rate' => $multiplier,
                        ];
                    }
                }
            }

            // COP P2P
            $copResponse = Http::timeout(6)->post('https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search', [
                'asset' => 'USDT',
                'fiat' => 'COP',
                'merchantCheck' => false,
                'page' => 1,
                'rows' => 3,
                'payTypes' => [],
                'tradeType' => 'BUY',
            ]);

            if ($copResponse->successful()) {
                $price = $copResponse->json('data.0.adv.price');
                if (is_numeric($price) && (float) $price > 0) {
                    $quote = (string) $price;
                    $rates[] = [
                        'source' => 'binance',
                        'from_currency' => 'COP',
                        'to_currency' => 'USD',
                        'quote_rate' => $quote,
                        'rate' => bcdiv('1', $quote, 12),
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to fetch rates from Binance P2P: '.$e->getMessage());
        }

        // 3. Colombia Official TRM (Open Exchange Rates fallback)
        try {
            $erResponse = Http::timeout(6)->get('https://open.er-api.com/v6/latest/USD');
            if ($erResponse->successful()) {
                $copRate = $erResponse->json('rates.COP');
                if (is_numeric($copRate) && (float) $copRate > 0) {
                    $quote = (string) $copRate;
                    $multiplier = bcdiv('1', $quote, 12);
                    $rates[] = [
                        'source' => 'official',
                        'from_currency' => 'COP',
                        'to_currency' => 'USD',
                        'quote_rate' => $quote,
                        'rate' => $multiplier,
                    ];
                    // Default fallback for COP
                    $rates[] = [
                        'source' => 'default',
                        'from_currency' => 'COP',
                        'to_currency' => 'USD',
                        'quote_rate' => $quote,
                        'rate' => $multiplier,
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to fetch rates from Open Exchange Rates: '.$e->getMessage());
        }

        return $rates;
    }
}
