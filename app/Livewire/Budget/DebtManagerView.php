<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Application\Budget\Actions\CalculateBudgetWaterfallAction;
use App\Application\Budget\DTOs\CalculateBudgetWaterfallDTO;
use App\Domain\Budget\Enums\DebtStrategy;
use App\Domain\Budget\Services\DebtPrioritizer;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Centro de Deudas | Fincab')]
class DebtManagerView extends Component
{
    public ?int $selectedWorkspaceId = null;

    public ?int $selectedPeriodId = null;

    public string $activeStrategy = 'avalanche';

    public string $simulatorExtra = '50.00';

    public bool $showDebtModal = false;

    public bool $showAdvancedOptions = false;

    public string $debtName = '';

    public string $creditor = '';

    public string $currencyCode = 'USD';

    public string $totalAmount = '';

    public string $initialBalance = '0.00';

    public string $currentBalance = '0.00';

    public string $interestRate = '0.00';

    public string $minimumPayment = '0.00';

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
            $this->activeStrategy = (string) $period->debt_strategy;
        }
    }

    public function switchStrategy(string $strategy, CalculateBudgetWaterfallAction $waterfallAction): void
    {
        $this->activeStrategy = $strategy;

        if ($this->selectedPeriodId) {
            $period = BudgetPeriod::query()->findOrFail($this->selectedPeriodId);
            $period->update(['debt_strategy' => $strategy]);

            $waterfallAction->execute(new CalculateBudgetWaterfallDTO(
                budgetPeriodId: $period->id,
            ));
        }
    }

    public function openDebtModal(): void
    {
        $this->reset([
            'debtName',
            'creditor',
            'totalAmount',
            'initialBalance',
            'currentBalance',
            'interestRate',
            'minimumPayment',
            'showAdvancedOptions',
        ]);
        $this->currencyCode = 'USD';
        $this->interestRate = '0.00';
        $this->minimumPayment = '0.00';
        $this->showDebtModal = true;
    }

    public function createDebt(): void
    {
        if ($this->totalAmount === '' && $this->initialBalance !== '0.00') {
            $this->totalAmount = $this->initialBalance;
        }

        $this->validate([
            'debtName' => 'required|string|max:255',
            'totalAmount' => 'required|numeric|gt:0',
            'interestRate' => 'nullable|numeric|gte:0',
            'minimumPayment' => 'nullable|numeric|gte:0',
        ], [
            'debtName.required' => 'El nombre del pasivo es obligatorio.',
            'totalAmount.required' => 'El monto total de la deuda es obligatorio.',
            'totalAmount.numeric' => 'El monto total debe ser un número válido.',
            'totalAmount.gt' => 'El monto total debe ser mayor a 0.',
            'interestRate.numeric' => 'La tasa de interés debe ser un número.',
            'interestRate.gte' => 'La tasa de interés no puede ser negativa.',
            'minimumPayment.numeric' => 'La cuota mínima debe ser un número.',
            'minimumPayment.gte' => 'La cuota mínima no puede ser negativa.',
        ]);

        if ($this->selectedWorkspaceId) {
            $amount = (string) $this->totalAmount;
            $rate = ($this->interestRate !== '' && is_numeric($this->interestRate)) ? (string) $this->interestRate : '0.00';
            $minimum = ($this->minimumPayment !== '' && is_numeric($this->minimumPayment)) ? (string) $this->minimumPayment : '0.00';

            Debt::query()->create([
                'workspace_id' => $this->selectedWorkspaceId,
                'name' => trim($this->debtName),
                'creditor' => trim($this->creditor) ?: null,
                'currency_code' => $this->currencyCode ?: 'USD',
                'initial_balance' => $amount,
                'current_balance' => $amount,
                'annual_interest_rate' => $rate,
                'minimum_payment' => $minimum,
                'is_active' => true,
            ]);

            $this->reset([
                'debtName',
                'creditor',
                'totalAmount',
                'initialBalance',
                'currentBalance',
                'interestRate',
                'minimumPayment',
                'showDebtModal',
                'showAdvancedOptions',
            ]);
        }
    }

    public function deleteDebt(int $debtId): void
    {
        if ($this->selectedWorkspaceId) {
            Debt::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->where('id', $debtId)
                ->delete();
        }
    }

    public function render(DebtPrioritizer $prioritizer): View
    {
        $debts = collect();
        $recentPayments = collect();

        if (is_int($this->selectedWorkspaceId)) {
            $debts = Debt::query()
                ->where('workspace_id', $this->selectedWorkspaceId)
                ->where('is_active', true)
                ->get();

            $recentPayments = DebtPayment::query()
                ->whereHas('debt', fn ($q) => $q->where('workspace_id', $this->selectedWorkspaceId))
                ->with('debt')
                ->latest()
                ->take(10)
                ->get();
        }

        $debtsArray = [];
        foreach ($debts as $d) {
            $debtsArray[] = [
                'id' => (int) $d->id,
                'current_balance' => $this->toNumeric($d->current_balance),
                'annual_interest_rate' => $this->toNumeric($d->annual_interest_rate),
            ];
        }

        $strategyEnum = DebtStrategy::tryFrom($this->activeStrategy) ?? DebtStrategy::Avalanche;
        $prioritized = $prioritizer->prioritize($debtsArray, $strategyEnum);

        $prioritizedIds = array_column($prioritized, 'id');
        $sortedDebts = $debts->sortBy(fn (Debt $d) => array_search($d->id, $prioritizedIds, true))->values();

        $extraAmount = is_numeric($this->simulatorExtra) ? (float) $this->simulatorExtra : 0.0;
        $simulationResults = [];
        if ($sortedDebts->isNotEmpty()) {
            $topDebt = $sortedDebts->first();
            $balance = (float) $topDebt->current_balance;
            $minPay = (float) $topDebt->minimum_payment;

            $monthsStandard = $minPay > 0 ? ceil($balance / $minPay) : 0;
            $monthsAccelerated = ($minPay + $extraAmount) > 0 ? ceil($balance / ($minPay + $extraAmount)) : 0;
            $monthsSaved = max(0, $monthsStandard - $monthsAccelerated);

            $simulationResults = [
                'targetDebtName' => $topDebt->name,
                'minPay' => $minPay,
                'monthsStandard' => $monthsStandard,
                'monthsAccelerated' => $monthsAccelerated,
                'monthsSaved' => $monthsSaved,
            ];
        }

        return view('livewire.budget.debt-manager-view', [
            'sortedDebts' => $sortedDebts,
            'recentPayments' => $recentPayments,
            'simulationResults' => $simulationResults,
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
