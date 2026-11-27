<?php

declare(strict_types=1);

namespace App\Application\Goal\Actions;

use App\Application\Goal\DTOs\GoalOverviewDTO;
use App\Domain\Goal\Enums\GoalStatus;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Services\GoalPacingCalculator;
use App\Domain\Goal\Services\RevenuePacingCalculator;
use App\Models\BudgetItem;
use App\Models\Debt;
use App\Models\FinancialTransaction;
use App\Models\Goal;
use App\Models\GoalContribution;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class GetGoalsOverviewAction
{
    public function __construct(
        private readonly GoalPacingCalculator $goalPacingCalculator = new GoalPacingCalculator,
        private readonly RevenuePacingCalculator $revenuePacingCalculator = new RevenuePacingCalculator,
    ) {}

    public function execute(int $workspaceId): GoalOverviewDTO
    {
        /** @var Collection<int, Goal> $allGoals */
        $allGoals = Goal::query()
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->with(['contributions' => fn ($q) => $q->latest('occurred_at')->take(5)])
            ->orderBy('id', 'asc')
            ->get();

        $hasActiveDebts = Debt::query()
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->exists();

        $hasPendingDebtBudgetItems = BudgetItem::query()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('debt_id')
            ->where('status', 'pending')
            ->exists();

        $hasActiveUncoveredDebts = $hasActiveDebts || $hasPendingDebtBudgetItems;

        $today = new DateTimeImmutable('today');
        $sinkingGoals = [];
        $revenueGoal = null;
        $totalSaved = '0.00000000';
        $activeCount = 0;
        $completedCount = 0;

        foreach ($allGoals as $g) {
            if ($g->type === GoalType::TargetAmount) {
                $totalSaved = bcadd($totalSaved, (string) $g->current_amount, 8);
                if ($g->status === GoalStatus::Completed) {
                    $completedCount++;
                } else {
                    $activeCount++;
                }

                $targetDateStr = $g->target_date ? $g->target_date->toDateString() : $today->modify('+1 year')->format('Y-m-d');
                $pacing = $this->goalPacingCalculator->calculatePacing(
                    targetAmount: (string) $g->target_amount,
                    currentAmount: (string) $g->current_amount,
                    startDate: new DateTimeImmutable($g->start_date->toDateString()),
                    targetDate: new DateTimeImmutable($targetDateStr),
                    currentDate: $today,
                    monthlyContributionRate: (string) $g->planned_monthly_quota,
                );

                /** @var array<int, array{id: int, amount: string, occurred_at: string, notes: string|null}> $recentContributions */
                $recentContributions = [];
                /** @var GoalContribution $c */
                foreach ($g->contributions as $c) {
                    $recentContributions[] = [
                        'id' => (int) $c->id,
                        'amount' => (string) $c->amount,
                        'occurred_at' => $c->occurred_at->format('Y-m-d'),
                        'notes' => $c->notes,
                    ];
                }

                $sinkingGoals[] = [
                    'id' => (int) $g->id,
                    'name' => $g->name,
                    'currency_code' => $g->currency_code,
                    'target_amount' => (string) $g->target_amount,
                    'current_amount' => (string) $g->current_amount,
                    'remaining_amount' => $pacing['remaining_amount'],
                    'completion_percent' => $pacing['completion_percent'],
                    'planned_monthly_quota' => (string) $g->planned_monthly_quota,
                    'required_monthly_quota' => $pacing['required_monthly_quota'],
                    'start_date' => $g->start_date->toDateString(),
                    'target_date' => $g->target_date?->toDateString(),
                    'projected_date' => $pacing['projected_completion_date']->format('Y-m-d'),
                    'status' => $g->status->value,
                    'pacing_status' => $pacing['status']->value,
                    'notes' => $g->notes,
                    'recent_contributions' => $recentContributions,
                ];
            } elseif ($revenueGoal === null) {
                $startOfMonth = CarbonImmutable::now()->startOfMonth();
                $endOfMonth = CarbonImmutable::now()->endOfMonth();

                $currentMonthRevenue = (string) FinancialTransaction::query()
                    ->where('workspace_id', $workspaceId)
                    ->where('transaction_type', 'income')
                    ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                    ->sum('amount_usd');

                if (bccomp($this->toNumeric($currentMonthRevenue), '0', 8) === 0) {
                    $currentMonthRevenue = (string) BudgetItem::query()
                        ->where('workspace_id', $workspaceId)
                        ->where('item_type', 'income')
                        ->sum(DB::raw('amount_q1 + amount_q2'));
                }

                $revPacing = $this->revenuePacingCalculator->calculateRevenuePacing(
                    monthlyTarget: (string) $g->monthly_target,
                    currentMonthRevenue: $this->toNumeric($currentMonthRevenue),
                    currentDate: $today,
                );

                $revenueGoal = [
                    'id' => (int) $g->id,
                    'name' => $g->name,
                    'currency_code' => $g->currency_code,
                    'monthly_target' => (string) $g->monthly_target,
                    'current_revenue' => $revPacing['current_revenue'],
                    'remaining_revenue' => $revPacing['remaining_revenue'],
                    'completion_percent' => $revPacing['completion_percent'],
                    'required_daily_rate' => $revPacing['required_daily_rate'],
                    'day_of_month' => $revPacing['day_of_month'],
                    'days_in_month' => $revPacing['days_in_month'],
                    'days_remaining' => $revPacing['days_remaining'],
                    'pacing_status' => $revPacing['status']->value,
                    'notes' => $g->notes,
                ];
            }
        }

        return new GoalOverviewDTO(
            sinkingFundGoals: $sinkingGoals,
            monthlyRevenueGoal: $revenueGoal,
            hasActiveUncoveredDebts: $hasActiveUncoveredDebts,
            totalSavedInGoals: $totalSaved,
            activeGoalsCount: $activeCount,
            completedGoalsCount: $completedCount,
        );
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
