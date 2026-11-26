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
        /** @var self|null $record */
        $record = self::query()
            ->where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to))
            ->where('source', $source)
            ->latest('effective_on')
            ->first();

        return $record?->quote_rate !== null ? (string) $record->quote_rate : null;
    }

    public static function getMultiplier(string $from, string $to = 'USD', string $source = 'default'): ?string
    {
        /** @var self|null $record */
        $record = self::query()
            ->where('from_currency', strtoupper($from))
            ->where('to_currency', strtoupper($to))
            ->where('source', $source)
            ->latest('effective_on')
            ->first();

        return $record?->rate !== null ? (string) $record->rate : null;
    }
}
