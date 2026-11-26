<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'budget_period_id', 'key', 'name', 'allocation_percent', 'priority'])]
class BudgetBucket extends Model
{
    /** @return BelongsTo<BudgetPeriod, $this> */
    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    /** @return HasMany<BudgetItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'allocation_percent' => 'decimal:2',
            'priority' => 'integer',
        ];
    }
}
