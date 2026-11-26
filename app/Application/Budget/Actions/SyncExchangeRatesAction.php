<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Models\CurrencyRate;
use App\Ports\Out\ExchangeRate\ExchangeRateProviderPort;
use Carbon\Carbon;

final class SyncExchangeRatesAction
{
    public function __construct(
        private readonly ExchangeRateProviderPort $provider,
    ) {}

    /**
     * @return array<int, CurrencyRate>
     */
    public function execute(?string $effectiveOn = null): array
    {
        $date = $effectiveOn ?? Carbon::today()->format('Y-m-d');
        $rawRates = $this->provider->fetchRates();

        $savedRates = [];
        foreach ($rawRates as $item) {
            /** @var CurrencyRate|null $rateRecord */
            $rateRecord = CurrencyRate::query()
                ->where('from_currency', $item['from_currency'])
                ->where('to_currency', $item['to_currency'])
                ->where('source', $item['source'])
                ->whereDate('effective_on', $date)
                ->first();

            if (! $rateRecord) {
                $rateRecord = new CurrencyRate([
                    'from_currency' => $item['from_currency'],
                    'to_currency' => $item['to_currency'],
                    'source' => $item['source'],
                    'effective_on' => $date,
                ]);
            }

            $rateRecord->fill([
                'quote_rate' => $item['quote_rate'],
                'rate' => $item['rate'],
            ]);
            $rateRecord->save();

            $savedRates[] = $rateRecord;
        }

        return $savedRates;
    }
}
