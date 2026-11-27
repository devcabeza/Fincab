<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $goal_id
 * @property int|null $financial_transaction_id
 * @property numeric-string $amount
 * @property string $currency_code
 * @property CarbonImmutable $occurred_at
 * @property string|null $notes
 * @property-read Workspace $workspace
 * @property-read Goal $goal
 * @property-read FinancialTransaction|null $financialTransaction
 */
#[Fillable([
    'workspace_id',
    'goal_id',
    'financial_transaction_id',
    'amount',
    'currency_code',
    'occurred_at',
    'notes',
])]
class GoalContribution extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Goal, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /** @return BelongsTo<FinancialTransaction, $this> */
    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
