<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['workspace_id', 'account_id', 'counter_account_id', 'budget_item_id', 'debt_id', 'transaction_type', 'amount_original', 'currency_code', 'exchange_rate_to_usd', 'amount_usd', 'account_amount', 'account_currency_code', 'counter_account_amount', 'counter_account_currency_code', 'idempotency_key', 'occurred_at', 'notes'])]
class FinancialTransaction extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function counterAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counter_account_id');
    }

    /** @return BelongsTo<BudgetItem, $this> */
    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    /** @return BelongsTo<Debt, $this> */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /** @return HasOne<DebtPayment, $this> */
    public function debtPayment(): HasOne
    {
        return $this->hasOne(DebtPayment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_original' => 'decimal:8',
            'exchange_rate_to_usd' => 'decimal:12',
            'amount_usd' => 'decimal:8',
            'account_amount' => 'decimal:8',
            'counter_account_amount' => 'decimal:8',
            'occurred_at' => 'datetime',
        ];
    }
}
