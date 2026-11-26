<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\BudgetItemObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'budget_period_id', 'budget_bucket_id', 'debt_id', 'account_id', 'title', 'item_type', 'income_category', 'split_mode', 'amount_q1', 'amount_q2', 'status', 'is_recurring', 'minimum_payment_protected'])]
#[ObservedBy([BudgetItemObserver::class])]
class BudgetItem extends Model
{
    public const INCOME_CATEGORY_SALARY = 'salary';

    public const INCOME_CATEGORY_SIDE_PROJECTS = 'side_projects';

    public const INCOME_CATEGORY_INVESTMENTS = 'investments';

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

    /** @return BelongsTo<BudgetPeriod, $this> */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /** @return BelongsTo<BudgetBucket, $this> */
    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    /** @return BelongsTo<Debt, $this> */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_q1' => 'decimal:8',
            'amount_q2' => 'decimal:8',
            'is_recurring' => 'boolean',
            'minimum_payment_protected' => 'boolean',
        ];
    }
}
