<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['owner_id', 'name', 'type', 'base_currency'])]
class Workspace extends Model
{
    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<Account, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** @return HasMany<BudgetPeriod, $this> */
    public function budgetPeriods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    /** @return HasMany<Debt, $this> */
    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
