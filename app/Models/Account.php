<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'name', 'currency_code', 'opening_balance', 'is_active'])]
class Account extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function counterTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'counter_account_id');
    }

    /**
     * @return numeric-string
     */
    public function getBalanceInUsd(): string
    {
        $bal = (string) $this->opening_balance;
        if (strtoupper($this->currency_code) === 'USD') {
            return $bal;
        }

        $multiplier = CurrencyRate::getMultiplier($this->currency_code, 'USD');
        if ($multiplier !== null && is_numeric($multiplier) && (float) $multiplier > 0) {
            return bcmul($bal, (string) $multiplier, 8);
        }

        $quote = CurrencyRate::getQuote($this->currency_code, 'USD');
        if ($quote !== null && is_numeric($quote) && (float) $quote > 0) {
            return bcdiv($bal, (string) $quote, 8);
        }

        return '0.00000000';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }
}
