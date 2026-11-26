<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'name', 'creditor', 'currency_code', 'initial_balance', 'current_balance', 'annual_interest_rate', 'minimum_payment', 'payment_frequency', 'is_active'])]
class Debt extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<BudgetItem, $this> */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /** @return HasMany<DebtPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:8',
            'current_balance' => 'decimal:8',
            'annual_interest_rate' => 'decimal:8',
            'minimum_payment' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }
}
