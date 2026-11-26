<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Application\Budget\Actions\SyncExchangeRatesAction;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;
use Throwable;

class ExchangeRatesBanner extends Component
{
    public function render(): View
    {
        $today = Carbon::today()->format('Y-m-d');

        // Silent automatic daily sync check (cached for 2 hours)
        if (! app()->runningUnitTests()) {
            Cache::remember('currency_rates_daily_sync_'.$today, 7200, function () use ($today): bool {
                $hasCompleteRates = CurrencyRate::query()
                    ->whereDate('effective_on', $today)
                    ->where('source', 'bcv')
                    ->exists();

                if (! $hasCompleteRates) {
                    try {
                        app(SyncExchangeRatesAction::class)->execute($today);
                    } catch (Throwable) {
                        // Fail silently and gracefully fall back to latest known rates in database
                    }
                }

                return true;
            });
        }

        $bcv = CurrencyRate::getQuote('VES', 'USD', 'bcv');
        $paralelo = CurrencyRate::getQuote('VES', 'USD', 'paralelo');
        $binanceVes = CurrencyRate::getQuote('VES', 'USD', 'binance');
        $cop = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance');

        return view('components.exchange-rates-banner', [
            'bcv' => $bcv,
            'paralelo' => $paralelo,
            'binanceVes' => $binanceVes,
            'cop' => $cop,
        ]);
    }
}
