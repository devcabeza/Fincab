<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Application\Budget\Actions\CalculateBudgetWaterfallAction;
use App\Application\Budget\Actions\CloneBudgetPeriodAction;
use App\Application\Budget\Actions\GetCashFlowSummaryAction;
use App\Application\Budget\DTOs\CalculateBudgetWaterfallDTO;
use App\Application\Budget\DTOs\CloneBudgetPeriodDTO;
use App\Application\Budget\DTOs\GetCashFlowSummaryDTO;
use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\CurrencyRate;
use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Panel Presupuestario | Fincab')]
class DashboardOverview extends Component
{
    public ?int $selectedWorkspaceId = null;

    public ?int $selectedPeriodId = null;

    public string $quickTitle = '';

    public string $quickAmount = '';

    public string $quickCurrency = 'USD';

    public string $quickRateSource = 'bcv';

    public string $quickLocalAmount = '';

    public string $quickManualQuote = '';

    public ?int $quickDebtId = null;

    public ?int $quickAccountId = null;

    public bool $quickIsRecurring = false;

    public string $cloneTargetPeriod = '';

    public bool $showQuickEntryModal = false;

    public bool $showCloneModal = false;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $workspace = $user->workspaces()->first();
            if (! $workspace) {
                $workspace = Workspace::query()->create([
                    'owner_id' => $user->id,
                    'name' => 'Finanzas Personales',
                    'type' => 'personal',
                    'base_currency' => 'USD',
                ]);
            }

            $this->selectedWorkspaceId = $workspace->id;

            $period = BudgetPeriod::query()
                ->where('workspace_id', $workspace->id)
                ->latest('period_start')
                ->first();

            if (! $period) {
                $period = BudgetPeriod::query()->create([
                    'workspace_id' => $workspace->id,
                    'period_start' => now()->startOfMonth()->format('Y-m-d'),
                    'base_currency' => 'USD',
                    'debt_strategy' => 'avalanche',
                ]);
            }

            $this->selectedPeriodId = $period->id;
        }
    }

    public function switchWorkspace(int $workspaceId): void
    {
        $this->selectedWorkspaceId = $workspaceId;
        $period = BudgetPeriod::query()
            ->where('workspace_id', $workspaceId)
            ->latest('period_start')
            ->first();

        $this->selectedPeriodId = $period?->id;
    }

    public function switchPeriod(int $periodId): void
    {
        $this->selectedPeriodId = $periodId;
    }

    public function recalculateWaterfall(CalculateBudgetWaterfallAction $action): void
    {
        if ($this->selectedPeriodId) {
            $action->execute(new CalculateBudgetWaterfallDTO(
                budgetPeriodId: $this->selectedPeriodId,
            ));
        }
    }

    public function updatedQuickCurrency(string $val): void
    {
        $this->recalculateQuickFromLocalAmount();
    }

    public function updatedQuickRateSource(string $val): void
    {
        $this->recalculateQuickFromLocalAmount();
    }

    public function updatedQuickLocalAmount(string $val): void
    {
        $this->recalculateQuickFromLocalAmount();
    }

    public function updatedQuickManualQuote(string $val): void
    {
        $this->recalculateQuickFromLocalAmount();
    }

    public function selectQuickRateSource(string $source): void
    {
        $this->quickRateSource = $source;
        $this->recalculateQuickFromLocalAmount();
    }

    public function getQuickExchangeRate(): ?string
    {
        if ($this->quickCurrency === 'USD') {
            return '1.00';
        }

        if ($this->quickRateSource === 'manual') {
            return is_numeric($this->quickManualQuote) && (float) $this->quickManualQuote > 0 ? (string) $this->quickManualQuote : null;
        }

        $quote = CurrencyRate::getQuote($this->quickCurrency, 'USD', $this->quickRateSource);
        if ($quote === null && $this->quickCurrency === 'COP') {
            $quote = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance');
        }

        return $quote;
    }

    public function recalculateQuickFromLocalAmount(): void
    {
        if ($this->quickCurrency === 'USD') {
            return;
        }

        if (empty($this->quickLocalAmount) || ! is_numeric($this->quickLocalAmount) || (float) $this->quickLocalAmount <= 0) {
            return;
        }

        $rate = $this->getQuickExchangeRate();
        if ($rate && (float) $rate > 0) {
            $this->quickAmount = bcdiv((string) $this->quickLocalAmount, $rate, 2);
        }
    }

    public function saveQuickEntry(QuincenaCalculator $quincenaCalculator): void
    {
        $this->validate([
            'quickTitle' => 'required|string|max:255',
            'quickAmount' => 'required|numeric|gt:0',
        ], [
            'quickTitle.required' => 'El título es obligatorio.',
            'quickAmount.required' => 'El monto debe ser mayor a 0.',
        ]);

        if ($this->selectedWorkspaceId && $this->selectedPeriodId) {
            $amount = is_numeric($this->quickAmount) ? (string) $this->quickAmount : '0.00000000';
            $split = $quincenaCalculator->calculateSplit($amount, SplitMode::Equal);

            BudgetItem::query()->create([
                'workspace_id' => $this->selectedWorkspaceId,
                'budget_period_id' => $this->selectedPeriodId,
                'debt_id' => $this->quickDebtId,
                'account_id' => $this->quickAccountId,
                'title' => trim($this->quickTitle),
                'item_type' => 'expense',
                'split_mode' => 'equal',
                'amount_q1' => $split['q1'],
                'amount_q2' => $split['q2'],
                'status' => 'paid',
                'is_recurring' => $this->quickIsRecurring,
            ]);

            $this->reset([
                'quickTitle',
                'quickAmount',
                'quickDebtId',
                'quickAccountId',
                'quickIsRecurring',
                'quickCurrency',
                'quickRateSource',
                'quickLocalAmount',
                'quickManualQuote',
                'showQuickEntryModal',
            ]);

            $this->dispatch('budget-item-updated');
        }
    }

    #[On('budget-item-updated')]
    #[On('account-updated')]
    public function refreshMetrics(): void
    {
        // Re-renders with fresh metrics
    }

    public function cloneMonth(CloneBudgetPeriodAction $action): void
    {
        $this->validate([
            'cloneTargetPeriod' => 'required|date_format:Y-m-d',
        ], [
            'cloneTargetPeriod.required' => 'La fecha de inicio del nuevo mes es obligatoria.',
        ]);

        if ($this->selectedPeriodId) {
            $newPeriod = $action->execute(new CloneBudgetPeriodDTO(
                sourcePeriodId: $this->selectedPeriodId,
                targetPeriodStart: $this->cloneTargetPeriod,
            ));

            $this->selectedPeriodId = $newPeriod->id;
            $this->reset(['cloneTargetPeriod', 'showCloneModal']);
        }
    }

    public function render(GetCashFlowSummaryAction $cashFlowAction, CalculateBudgetWaterfallAction $waterfallAction): View
    {
        $user = Auth::user();
        $workspaces = $user ? $user->workspaces()->get() : collect();

        $currentWorkspace = $workspaces->firstWhere('id', $this->selectedWorkspaceId) ?? $workspaces->first();

        $periods = collect();
        $debts = collect();
        $accounts = collect();

        if ($currentWorkspace instanceof Workspace) {
            $periods = BudgetPeriod::query()->where('workspace_id', $currentWorkspace->id)->orderByDesc('period_start')->get();
            $debts = Debt::query()->where('workspace_id', $currentWorkspace->id)->where('is_active', true)->get();
            $accounts = Account::query()->where('workspace_id', $currentWorkspace->id)->get();
        }

        $currentPeriod = $periods->firstWhere('id', $this->selectedPeriodId) ?? $periods->first();

        $totalInitialDebt = '0.00000000';
        $totalCurrentDebt = '0.00000000';
        foreach ($debts as $debt) {
            $totalInitialDebt = bcadd($totalInitialDebt, $this->toNumeric($debt->initial_balance), 8);
            $totalCurrentDebt = bcadd($totalCurrentDebt, $this->toNumeric($debt->current_balance), 8);
        }

        $debtProgressPercent = bccomp($totalInitialDebt, '0', 8) > 0
            ? (float) bcdiv(bcmul(bcsub($totalInitialDebt, $totalCurrentDebt, 8), '100', 8), $totalInitialDebt, 2)
            : 0.0;

        $totalLiquidCash = '0.00000000';
        foreach ($accounts as $acc) {
            $totalLiquidCash = bcadd($totalLiquidCash, $this->toNumeric($acc->opening_balance), 8);
        }

        $cashFlowSummary = null;
        $waterfallResult = null;
        $bucketsWithProgress = [];

        if ($currentPeriod instanceof BudgetPeriod) {
            $cashFlowSummary = $cashFlowAction->execute(new GetCashFlowSummaryDTO(
                budgetPeriodId: $currentPeriod->id,
            ));

            $waterfallResult = $waterfallAction->execute(new CalculateBudgetWaterfallDTO(
                budgetPeriodId: $currentPeriod->id,
            ));

            $currentPeriod->load(['buckets.items']);
            foreach ($currentPeriod->buckets as $bucket) {
                $spent = '0.00000000';
                foreach ($bucket->items as $item) {
                    $total = bcadd($this->toNumeric($item->amount_q1), $this->toNumeric($item->amount_q2), 8);
                    $spent = bcadd($spent, $total, 8);
                }

                $limit = $this->toNumeric($waterfallResult->bucketLimits[$bucket->key] ?? '0.00000000');
                $percent = bccomp($limit, '0', 8) > 0
                    ? (float) bcdiv(bcmul($spent, '100', 8), $limit, 2)
                    : (bccomp($spent, '0', 8) > 0 ? 100.0 : 0.0);

                $bucketsWithProgress[] = [
                    'key' => $bucket->key,
                    'name' => $bucket->name,
                    'spent' => $spent,
                    'limit' => $limit,
                    'percent' => min(100.0, $percent),
                ];
            }
        }

        $latestBcv = CurrencyRate::getQuote('VES', 'USD', 'bcv');
        $latestParalelo = CurrencyRate::getQuote('VES', 'USD', 'paralelo');
        $latestBinanceVes = CurrencyRate::getQuote('VES', 'USD', 'binance');
        $latestCop = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance');

        $accounts = $this->selectedWorkspaceId
            ? Account::query()->where('workspace_id', $this->selectedWorkspaceId)->where('is_active', true)->get()
            : collect();

        return view('livewire.budget.dashboard-overview', [
            'workspaces' => $workspaces,
            'currentWorkspace' => $currentWorkspace,
            'periods' => $periods,
            'currentPeriod' => $currentPeriod,
            'accounts' => $accounts,
            'debts' => $debts,
            'totalCurrentDebt' => $totalCurrentDebt,
            'totalInitialDebt' => $totalInitialDebt,
            'debtProgressPercent' => $debtProgressPercent,
            'totalLiquidCash' => $totalLiquidCash,
            'cashFlowSummary' => $cashFlowSummary,
            'waterfallResult' => $waterfallResult,
            'bucketsWithProgress' => $bucketsWithProgress,
            'latestBcv' => $latestBcv,
            'latestParalelo' => $latestParalelo,
            'latestBinanceVes' => $latestBinanceVes,
            'latestCop' => $latestCop,
        ]);
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
