<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'period_start', 'base_currency', 'debt_strategy', 'extra_debt_payment_percent'])]
class BudgetPeriod extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<BudgetBucket, $this> */
    public function buckets(): HasMany
    {
        return $this->hasMany(BudgetBucket::class);
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
            'period_start' => 'date',
            'extra_debt_payment_percent' => 'decimal:2',
        ];
    }
}
