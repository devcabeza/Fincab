<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Domain\Budget\Enums\ItemStatus;
use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class MobileDashboardScreen extends NativeComponent
{
    public int $workspaceId = 0;

    public ?int $periodId = null;

    public function mount(?int $workspaceId = null, ?int $periodId = null): void
    {
        if ($workspaceId !== null && $workspaceId > 0) {
            $this->workspaceId = $workspaceId;
        } else {
            $first = Workspace::query()->first();
            $this->workspaceId = $first !== null ? $first->id : 0;
        }

        if ($periodId !== null && $periodId > 0) {
            $this->periodId = $periodId;
        } else {
            $period = BudgetPeriod::query()
                ->where('workspace_id', $this->workspaceId)
                ->orderByDesc('period_start')
                ->first();
            $this->periodId = $period?->id;
        }
    }

    public function markAsPaid(?int $itemId = null): void
    {
        $query = BudgetItem::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('status', ItemStatus::Pending->value);

        if ($itemId !== null) {
            $query->where('id', $itemId);
        }

        $item = $query->first();

        if ($item !== null) {
            $item->update(['status' => ItemStatus::Paid->value]);
        }
    }

    public function goToQuickEntry(): void
    {
        $this->navigate('/mobile/quick-entry', [
            'workspaceId' => $this->workspaceId,
            'periodId' => $this->periodId,
        ]);
    }

    public function goToDebts(): void
    {
        $this->navigate('/mobile/debts', [
            'workspaceId' => $this->workspaceId,
            'periodId' => $this->periodId,
        ]);
    }

    public function refreshData(): void
    {
        // Refresh trigger
    }

    public function render(): View
    {
        $workspace = Workspace::query()->find($this->workspaceId);
        $period = $this->periodId !== null ? BudgetPeriod::query()->find($this->periodId) : null;

        $accounts = Account::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('is_active', true)
            ->get();

        $liquidCash = '0.00';
        foreach ($accounts as $acc) {
            $liquidCash = bcadd($liquidCash, (string) $acc->opening_balance, 2);
        }

        $debts = Debt::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('is_active', true)
            ->get();

        $totalDebt = '0.00';
        $initialDebt = '0.00';
        foreach ($debts as $d) {
            $totalDebt = bcadd($totalDebt, (string) $d->current_balance, 2);
            $initialDebt = bcadd($initialDebt, (string) $d->initial_balance, 2);
        }

        $debtProgress = 0.0;
        if (bccomp($initialDebt, '0.00', 2) > 0) {
            $paidOff = bcsub($initialDebt, $totalDebt, 2);
            $debtProgress = (float) bcdiv($paidOff, $initialDebt, 4);
            $debtProgress = max(0.0, min(1.0, $debtProgress));
        }

        $items = BudgetItem::query()
            ->where('workspace_id', $this->workspaceId)
            ->when($this->periodId !== null, fn ($q) => $q->where('budget_period_id', $this->periodId))
            ->orderBy('id', 'desc')
            ->get();

        $committedQ1 = '0.00';
        $committedQ2 = '0.00';
        $pendingItems = [];

        foreach ($items as $item) {
            $committedQ1 = bcadd($committedQ1, (string) $item->amount_q1, 2);
            $committedQ2 = bcadd($committedQ2, (string) $item->amount_q2, 2);

            if ($item->status === ItemStatus::Pending->value) {
                $pendingItems[] = $item;
            }
        }

        return view('native.mobile-dashboard-screen', [
            'workspace' => $workspace,
            'period' => $period,
            'liquidCash' => number_format((float) $liquidCash, 2),
            'totalDebt' => number_format((float) $totalDebt, 2),
            'debtProgress' => $debtProgress,
            'committedQ1' => number_format((float) $committedQ1, 2),
            'committedQ2' => number_format((float) $committedQ2, 2),
            'pendingItems' => $pendingItems,
        ]);
    }
}
