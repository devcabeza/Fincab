<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Domain\Budget\Enums\DebtStrategy;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class MobileDebtScreen extends NativeComponent
{
    public int $workspaceId = 0;

    public ?int $periodId = null;

    public string $activeStrategy = 'avalanche';

    public string $statusMessage = '';

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

        if ($this->periodId !== null) {
            $period = BudgetPeriod::query()->find($this->periodId);
            if ($period !== null) {
                $this->activeStrategy = $period->debt_strategy;
            }
        }
    }

    public function setStrategy(string $strategy): void
    {
        $this->activeStrategy = $strategy;

        if ($this->periodId !== null) {
            BudgetPeriod::query()
                ->where('id', $this->periodId)
                ->update(['debt_strategy' => $strategy]);
        }

        $strategyLabel = $strategy === DebtStrategy::Snowball->value ? 'Bola de Nieve' : 'Avalancha';
        $this->statusMessage = "Estrategia cambiada a: {$strategyLabel}";
    }

    public function setSnowball(): void
    {
        $this->setStrategy(DebtStrategy::Snowball->value);
    }

    public function setAvalanche(): void
    {
        $this->setStrategy(DebtStrategy::Avalanche->value);
    }

    public function backToDashboard(): void
    {
        $this->navigate('/mobile', [
            'workspaceId' => $this->workspaceId,
            'periodId' => $this->periodId,
        ]);
    }

    public function render(): View
    {
        $query = Debt::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('is_active', true);

        if ($this->activeStrategy === DebtStrategy::Snowball->value) {
            $query->orderBy('current_balance', 'asc');
        } else {
            $query->orderBy('annual_interest_rate', 'desc');
        }

        $debts = $query->get();

        return view('native.mobile-debt-screen', [
            'debts' => $debts,
        ]);
    }
}
