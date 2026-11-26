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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }
}
