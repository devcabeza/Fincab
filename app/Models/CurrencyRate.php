<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $from_currency
 * @property string $to_currency
 * @property string $source
 * @property string|float $rate
 * @property string|float|null $quote_rate
 * @property Carbon|string|null $effective_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['from_currency', 'to_currency', 'source', 'rate', 'quote_rate', 'effective_on'])]
class CurrencyRate extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:12',
            'quote_rate' => 'decimal:8',
            'effective_on' => 'date',
        ];
    }

    public static function getQuote(string $from, string $to = 'USD', string $source = 'default'): ?string
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return '1.00000000';
        }

        /** @var self|null $record */
        $record = self::query()
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('source', $source)
            ->latest('effective_on')
            ->first();

        if ($record?->quote_rate !== null && is_numeric($record->quote_rate) && (float) $record->quote_rate > 0) {
            return (string) $record->quote_rate;
        }

        if ($source !== 'default') {
            return null;
        }

        $fallbackSources = match ($from) {
            'VES' => ['bcv', 'paralelo', 'binance'],
            'COP' => ['official', 'binance'],
            default => ['official', 'bcv', 'binance'],
        };

        foreach ($fallbackSources as $src) {
            /** @var self|null $fallbackRec */
            $fallbackRec = self::query()
                ->where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('source', $src)
                ->latest('effective_on')
                ->first();

            if ($fallbackRec?->quote_rate !== null && is_numeric($fallbackRec->quote_rate) && (float) $fallbackRec->quote_rate > 0) {
                return (string) $fallbackRec->quote_rate;
            }
        }

        /** @var self|null $anyRecord */
        $anyRecord = self::query()
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->latest('effective_on')
            ->first();

        return ($anyRecord?->quote_rate !== null && is_numeric($anyRecord->quote_rate)) ? (string) $anyRecord->quote_rate : null;
    }

    public static function getMultiplier(string $from, string $to = 'USD', string $source = 'default'): ?string
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return '1.000000000000';
        }

        if ($source !== 'default') {
            /** @var self|null $record */
            $record = self::query()
                ->where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('source', $source)
                ->latest('effective_on')
                ->first();

            if ($record?->rate !== null && is_numeric($record->rate) && (float) $record->rate > 0) {
                return (string) $record->rate;
            }

            if ($record?->quote_rate !== null && is_numeric($record->quote_rate) && (float) $record->quote_rate > 0) {
                return bcdiv('1', (string) $record->quote_rate, 12);
            }
        }

        $fallbackSources = match ($from) {
            'VES' => ['default', 'bcv', 'paralelo', 'binance'],
            'COP' => ['default', 'official', 'binance'],
            default => ['default', 'official', 'bcv', 'binance'],
        };

        foreach ($fallbackSources as $src) {
            if ($src === $source) {
                continue;
            }

            /** @var self|null $fallbackRec */
            $fallbackRec = self::query()
                ->where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('source', $src)
                ->latest('effective_on')
                ->first();

            if ($fallbackRec?->rate !== null && is_numeric($fallbackRec->rate) && (float) $fallbackRec->rate > 0) {
                return (string) $fallbackRec->rate;
            }

            if ($fallbackRec?->quote_rate !== null && is_numeric($fallbackRec->quote_rate) && (float) $fallbackRec->quote_rate > 0) {
                return bcdiv('1', (string) $fallbackRec->quote_rate, 12);
            }
        }

        /** @var self|null $anyRecord */
        $anyRecord = self::query()
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->latest('effective_on')
            ->first();

        if ($anyRecord?->rate !== null && is_numeric($anyRecord->rate) && (float) $anyRecord->rate > 0) {
            return (string) $anyRecord->rate;
        }

        if ($anyRecord?->quote_rate !== null && is_numeric($anyRecord->quote_rate) && (float) $anyRecord->quote_rate > 0) {
            return bcdiv('1', (string) $anyRecord->quote_rate, 12);
        }

        return null;
    }
}
