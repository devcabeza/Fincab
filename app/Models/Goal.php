<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Goal\Enums\GoalStatus;
use App\Domain\Goal\Enums\GoalType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property GoalType $type
 * @property GoalStatus $status
 * @property string $currency_code
 * @property numeric-string $target_amount
 * @property numeric-string $current_amount
 * @property numeric-string $monthly_target
 * @property numeric-string $planned_monthly_quota
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $target_date
 * @property CarbonImmutable|null $projected_date
 * @property string|null $notes
 * @property bool $is_active
 * @property-read Workspace $workspace
 * @property-read Collection<int, GoalContribution> $contributions
 */
#[Fillable([
    'workspace_id',
    'name',
    'type',
    'status',
    'currency_code',
    'target_amount',
    'current_amount',
    'monthly_target',
    'planned_monthly_quota',
    'start_date',
    'target_date',
    'projected_date',
    'notes',
    'is_active',
])]
class Goal extends Model
{
    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<GoalContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => GoalType::class,
            'status' => GoalStatus::class,
            'target_amount' => 'decimal:8',
            'current_amount' => 'decimal:8',
            'monthly_target' => 'decimal:8',
            'planned_monthly_quota' => 'decimal:8',
            'start_date' => 'immutable_date',
            'target_date' => 'immutable_date',
            'projected_date' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }
}
