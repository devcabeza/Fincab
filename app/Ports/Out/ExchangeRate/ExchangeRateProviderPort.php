<?php

declare(strict_types=1);

namespace App\Ports\Out\ExchangeRate;

interface ExchangeRateProviderPort
{
    /**
     * @return array<int, array{source: string, from_currency: string, to_currency: string, quote_rate: string, rate: string}>
     */
    public function fetchRates(): array;
}
