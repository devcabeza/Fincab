<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Budget\Actions\SyncExchangeRatesAction;
use Illuminate\Console\Command;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'rates:sync';

    protected $description = 'Sync exchange rates from public APIs (BCV, Paralelo, Binance P2P, COP)';

    public function handle(SyncExchangeRatesAction $action): int
    {
        $this->info('Fetching exchange rates...');
        $rates = $action->execute();

        foreach ($rates as $rate) {
            $this->line(sprintf(
                '[%s] %s -> %s: quote=%s (rate=%s)',
                $rate->source,
                $rate->from_currency,
                $rate->to_currency,
                (string) ($rate->quote_rate ?? 'N/A'),
                (string) $rate->rate
            ));
        }

        $this->info('Exchange rates synced successfully ('.count($rates).' rates).');

        return self::SUCCESS;
    }
}
