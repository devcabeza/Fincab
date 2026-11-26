<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Application\Budget\Actions\CreateBudgetItemAction;
use App\Application\Budget\Actions\UpdateBudgetItemAction;
use App\Application\Budget\DTOs\CreateBudgetItemDTO;
use App\Application\Budget\DTOs\UpdateBudgetItemDTO;
use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Exceptions\DiscretionaryExpenseBlockedException;
use App\Domain\Budget\Exceptions\ProtectedDebtMinimumException;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\CurrencyRate;
use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hoja Presupuestaria | Fincab')]
class BudgetSheetView extends Component
{
    public ?int $selectedWorkspaceId = null;

    public ?int $selectedPeriodId = null;

    public string $quincenaFilter = 'all';

    public string $bucketFilter = 'all';

    public string $typeFilter = 'all';

    public string $recurringFilter = 'all';

    public bool $showItemModal = false;

    public bool $showCurrencyConverter = false;

    public bool $showAdvancedOptions = false;

    public ?int $editingItemId = null;

    public string $title = '';

    public ?int $bucketId = null;

    public ?string $incomeCategory = null;

    public ?int $debtId = null;

    public ?int $accountId = null;

    public string $itemType = 'expense';

    public string $splitMode = 'equal';

    public string $totalAmount = '';

    public string $currency = 'USD';

    public string $rateSource = 'bcv';

    public string $localAmount = '';

    public string $manualQuote = '';

    public string $amountQ1 = '0.00';

    public string $amountQ2 = '0.00';

    public bool $isRecurring = false;

    public string $justification = '';

    public string $feedbackMessage = '';

    public function mount(?int $workspaceId = null, ?int $periodId = null): void
    {
        $user = Auth::user();
        if ($user) {
            $workspace = $workspaceId ? Workspace::query()->find($workspaceId) : $user->workspaces()->first();
            if (! $workspace) {
                $workspace = Workspace::query()->create([
                    'owner_id' => $user->id,
                    'name' => 'Finanzas Personales',
                    'type' => 'personal',
                    'base_currency' => 'USD',
                ]);
            }

            $this->selectedWorkspaceId = $workspace->id;

            $period = $periodId
                ? BudgetPeriod::query()->where('workspace_id', $workspace->id)->find($periodId)
                : BudgetPeriod::query()->where('workspace_id', $workspace->id)->latest('period_start')->first();

            if (! $period) {
                $period = BudgetPeriod::query()->create([
                    'workspace_id' => $workspace->id,
                    'period_start' => now()->startOfMonth()->format('Y-m-d'),
                    'base_currency' => 'USD',
                ]);
            }

            $this->selectedPeriodId = $period->id;
        }
    }

    public function toggleStatus(int $itemId): void
    {
        /** @var BudgetItem $item */
        $item = BudgetItem::query()->findOrFail($itemId);
        $newStatus = $item->status === 'paid' ? 'pending' : 'paid';

        $item->update([
            'status' => $newStatus,
        ]);

        $this->dispatch('budget-item-updated');
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingItemId', 'title', 'bucketId', 'incomeCategory', 'debtId', 'accountId', 'isRecurring', 'justification', 'localAmount', 'manualQuote', 'showCurrencyConverter', 'showAdvancedOptions']);
        $this->currency = 'USD';
        $this->rateSource = 'bcv';
        $this->itemType = 'expense';
        $this->incomeCategory = null;
        $this->splitMode = 'equal';
        $this->totalAmount = '';
        $this->amountQ1 = '0.00';
        $this->amountQ2 = '0.00';
        $this->showItemModal = true;
    }

    public function updatedItemType(string $val): void
    {
        if ($val === 'income') {
            if (empty($this->incomeCategory)) {
                $this->incomeCategory = BudgetItem::INCOME_CATEGORY_SALARY;
            }
            $this->debtId = null;
        } else {
            $this->incomeCategory = null;
        }
    }

    public function updatedCurrency(string $val): void
    {
        $this->recalculateFromLocalAmount();
    }

    public function updatedLocalAmount(string $val): void
    {
        $this->recalculateFromLocalAmount();
    }

    public function selectRateSource(string $source): void
    {
        $this->rateSource = $source;
        $this->syncAmountsAfterRateChange();
    }

    public function updatedRateSource(string $val): void
    {
        $this->syncAmountsAfterRateChange();
    }

    public function updatedManualQuote(string $val): void
    {
        $this->syncAmountsAfterRateChange();
    }

    public function updatedAccountId(?int $val): void
    {
        if (! $val || ! $this->selectedWorkspaceId) {
            $this->currency = 'USD';

            return;
        }

        /** @var Account|null $account */
        $account = Account::query()
            ->where('workspace_id', $this->selectedWorkspaceId)
            ->find($val);

        if (! $account) {
            return;
        }

        if ($account->currency_code !== 'USD') {
            $this->currency = $account->currency_code;
            $this->showCurrencyConverter = true;

            if ($this->currency === 'VES' && ! in_array($this->rateSource, ['bcv', 'paralelo', 'binance', 'manual'], true)) {
                $this->rateSource = 'bcv';
            } elseif ($this->currency === 'COP' && ! in_array($this->rateSource, ['official', 'binance', 'manual'], true)) {
                $this->rateSource = 'official';
            }

            if (! empty($this->totalAmount) && is_numeric($this->totalAmount) && (float) $this->totalAmount > 0) {
                $rate = $this->getActiveExchangeRate();
                if ($rate && (float) $rate > 0) {
                    $this->localAmount = bcmul((string) $this->totalAmount, $this->toNumeric($rate), 2);
                }
            }
        } else {
            $this->currency = 'USD';
        }
    }

    private function syncAmountsAfterRateChange(): void
    {
        $rate = $this->getActiveExchangeRate();
        if (! $rate || (float) $rate <= 0) {
            return;
        }

        if (! empty($this->localAmount) && is_numeric($this->localAmount) && (float) $this->localAmount > 0) {
            $usdTotal = bcdiv((string) $this->localAmount, $this->toNumeric($rate), 2);
            $this->totalAmount = $usdTotal;
            if ($this->splitMode === 'equal') {
                $this->recalculateEqualSplit();
            }
        } elseif (! empty($this->totalAmount) && is_numeric($this->totalAmount) && (float) $this->totalAmount > 0) {
            $this->localAmount = bcmul((string) $this->totalAmount, $this->toNumeric($rate), 2);
        }
    }

    public function getActiveExchangeRate(): ?string
    {
        if ($this->currency === 'USD') {
            return '1.00';
        }

        if ($this->rateSource === 'manual') {
            return is_numeric($this->manualQuote) && (float) $this->manualQuote > 0 ? (string) $this->manualQuote : null;
        }

        $quote = CurrencyRate::getQuote($this->currency, 'USD', $this->rateSource);
        if ($quote === null && $this->currency === 'COP') {
            $quote = CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance');
        }

        return $quote;
    }

    public function recalculateFromLocalAmount(): void
    {
        if ($this->currency === 'USD') {
            return;
        }

        if (empty($this->localAmount) || ! is_numeric($this->localAmount) || (float) $this->localAmount <= 0) {
            return;
        }

        $rate = $this->getActiveExchangeRate();
        if ($rate && (float) $rate > 0) {
            $usdTotal = bcdiv((string) $this->localAmount, $rate, 2);
            $this->totalAmount = $usdTotal;
            $this->recalculateEqualSplit();
        }
    }

    public function openEditModal(int $itemId): void
    {
        /** @var BudgetItem $item */
        $item = BudgetItem::query()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->title = $item->title;
        $this->bucketId = $item->budget_bucket_id;
        $this->debtId = $item->debt_id;
        $this->accountId = $item->account_id;
        $this->itemType = $item->item_type;
        $this->splitMode = $item->split_mode ?? 'equal';
        $this->amountQ1 = (string) $item->amount_q1;
        $this->amountQ2 = (string) $item->amount_q2;
        $this->totalAmount = bcadd((string) $item->amount_q1, (string) $item->amount_q2, 2);
        $this->isRecurring = (bool) $item->is_recurring;
        $this->incomeCategory = $item->income_category;
        $this->justification = '';
        $this->showCurrencyConverter = false;
        $this->showAdvancedOptions = $item->debt_id !== null;

        if ($item->account_id) {
            $account = Account::query()->find($item->account_id);
            if ($account && $account->currency_code !== 'USD') {
                $this->currency = $account->currency_code;
                $this->showCurrencyConverter = true;
                $rate = $this->getActiveExchangeRate();
                if ($rate && (float) $rate > 0) {
                    $this->localAmount = bcmul((string) $this->totalAmount, $this->toNumeric($rate), 2);
                }
            }
        }

        $this->showItemModal = true;
    }

    public function updatedTotalAmount(string $value): void
    {
        if ($this->showCurrencyConverter && $this->currency !== 'USD' && is_numeric($value) && (float) $value > 0) {
            $rate = $this->getActiveExchangeRate();
            if ($rate && (float) $rate > 0) {
                $this->localAmount = bcmul($value, $this->toNumeric($rate), 2);
            }
        }

        if ($this->splitMode === 'equal') {
            $this->recalculateEqualSplit();
        }
    }

    public function updatedAmountQ1(string $value): void
    {
        if ($this->splitMode === 'manual') {
            $this->recalculateTotalFromQuincenas();
        }
    }

    public function updatedAmountQ2(string $value): void
    {
        if ($this->splitMode === 'manual') {
            $this->recalculateTotalFromQuincenas();
        }
    }

    public function setDistribution(string $type): void
    {
        $total = $this->toNumeric($this->totalAmount);

        if ($type === 'equal') {
            $this->splitMode = 'equal';
            $calc = new QuincenaCalculator;
            $split = $calc->calculateSplit($total, SplitMode::Equal);
            $this->amountQ1 = number_format((float) $split['q1'], 2, '.', '');
            $this->amountQ2 = number_format((float) $split['q2'], 2, '.', '');
        } elseif ($type === 'q1_only') {
            $this->splitMode = 'manual';
            $this->amountQ1 = number_format((float) $total, 2, '.', '');
            $this->amountQ2 = '0.00';
        } elseif ($type === 'q2_only') {
            $this->splitMode = 'manual';
            $this->amountQ1 = '0.00';
            $this->amountQ2 = number_format((float) $total, 2, '.', '');
        } elseif ($type === 'manual') {
            $this->splitMode = 'manual';
        }
    }

    private function recalculateEqualSplit(): void
    {
        $total = $this->toNumeric($this->totalAmount);
        $calc = new QuincenaCalculator;
        $split = $calc->calculateSplit($total, SplitMode::Equal);
        $this->amountQ1 = number_format((float) $split['q1'], 2, '.', '');
        $this->amountQ2 = number_format((float) $split['q2'], 2, '.', '');
    }

    private function recalculateTotalFromQuincenas(): void
    {
        $total = bcadd($this->toNumeric($this->amountQ1), $this->toNumeric($this->amountQ2), 2);
        $this->totalAmount = number_format((float) $total, 2, '.', '');
    }

    public function saveItem(
        CreateBudgetItemAction $createAction,
        UpdateBudgetItemAction $updateAction,
        QuincenaCalculator $quincenaCalculator,
    ): void {
        if ($this->splitMode === 'equal' && ! empty($this->totalAmount)) {
            $this->recalculateEqualSplit();
        }
        $this->validate([
            'title' => 'required|string|max:255',
            'amountQ1' => 'required|numeric|gte:0',
            'amountQ2' => 'required|numeric|gte:0',
        ], [
            'title.required' => 'El título de la partida es obligatorio.',
            'amountQ1.required' => 'El monto para Q1 es obligatorio.',
            'amountQ2.required' => 'El monto para Q2 es obligatorio.',
        ]);

        if (! $this->selectedWorkspaceId || ! $this->selectedPeriodId) {
            return;
        }

        if ($this->itemType === 'income') {
            $this->debtId = null;
            $this->bucketId = null;
            if (empty($this->incomeCategory)) {
                $this->incomeCategory = BudgetItem::INCOME_CATEGORY_SALARY;
            }
        } else {
            $this->incomeCategory = null;
        }

        try {
            if ($this->editingItemId !== null) {
                $dto = new UpdateBudgetItemDTO(
                    budgetItemId: $this->editingItemId,
                    title: $this->title,
                    splitMode: $this->splitMode,
                    amountQ1: $this->amountQ1,
                    amountQ2: $this->amountQ2,
                    isRecurring: $this->isRecurring,
                    accountId: $this->accountId,
                    updateAccountId: true,
                    incomeCategory: $this->incomeCategory,
                    updateIncomeCategory: true,
                );

                $updateAction->execute($dto);
            } else {
                $note = null;
                if ($this->currency !== 'USD' && ! empty($this->localAmount)) {
                    $activeRate = $this->getActiveExchangeRate() ?? 'N/A';
                    $rateLabel = strtoupper($this->rateSource);
                    $note = "[{$this->localAmount} {$this->currency} @ {$activeRate} {$rateLabel}]";
                }
                $finalJustification = trim(($this->justification ? $this->justification.' ' : '').($note ?? ''));

                $dto = new CreateBudgetItemDTO(
                    workspaceId: $this->selectedWorkspaceId,
                    budgetPeriodId: $this->selectedPeriodId,
                    budgetBucketId: $this->bucketId,
                    title: $this->title,
                    itemType: $this->itemType,
                    incomeCategory: $this->incomeCategory,
                    splitMode: $this->splitMode,
                    amountQ1: $this->toNumeric($this->amountQ1),
                    amountQ2: $this->toNumeric($this->amountQ2),
                    status: 'pending',
                    debtId: $this->debtId,
                    accountId: $this->accountId,
                    isRecurring: $this->isRecurring,
                    justification: $finalJustification ?: null,
                );

                $createAction->execute($dto);
            }

            $this->dispatch('budget-item-updated');
            $this->showItemModal = false;
            $this->feedbackMessage = 'Partida guardada correctamente.';
        } catch (ProtectedDebtMinimumException $e) {
            throw ValidationException::withMessages([
                'amountQ1' => $e->getMessage(),
            ]);
        } catch (DiscretionaryExpenseBlockedException $e) {
            throw ValidationException::withMessages([
                'justification' => $e->getMessage().' Se requiere una justificación válida para continuar.',
            ]);
        }
    }

    public function deleteItem(int $itemId): void
    {
        BudgetItem::query()->where('id', $itemId)->delete();
        $this->dispatch('budget-item-updated');
    }

    public function render(): View
    {
        $buckets = $this->selectedPeriodId
            ? BudgetBucket::query()->where('budget_period_id', $this->selectedPeriodId)->orderBy('priority')->get()
            : collect();

        $debts = $this->selectedWorkspaceId
            ? Debt::query()->where('workspace_id', $this->selectedWorkspaceId)->where('is_active', true)->get()
            : collect();

        $accounts = $this->selectedWorkspaceId
            ? Account::query()->where('workspace_id', $this->selectedWorkspaceId)->where('is_active', true)->get()
            : collect();

        $query = BudgetItem::query()
            ->with(['budgetBucket', 'debt', 'account'])
            ->where('budget_period_id', $this->selectedPeriodId);

        if ($this->typeFilter === 'expense') {
            $query->where('item_type', 'expense');
        } elseif ($this->typeFilter === 'income') {
            $query->where('item_type', 'income');
        }

        if ($this->bucketFilter !== 'all') {
            $query->whereHas('budgetBucket', fn ($q) => $q->where('key', $this->bucketFilter));
        }

        $items = $query->get();

        if ($this->quincenaFilter === 'q1') {
            $items = $items->filter(fn (BudgetItem $item) => bccomp((string) $item->amount_q1, '0', 8) > 0);
        } elseif ($this->quincenaFilter === 'q2') {
            $items = $items->filter(fn (BudgetItem $item) => bccomp((string) $item->amount_q2, '0', 8) > 0);
        }

        if ($this->recurringFilter === 'recurring') {
            $items = $items->filter(fn (BudgetItem $item) => (bool) $item->is_recurring);
        }

        return view('livewire.budget.budget-sheet-view', [
            'items' => $items,
            'buckets' => $buckets,
            'debts' => $debts,
            'accounts' => $accounts,
            'typeFilter' => $this->typeFilter,
            'bcvQuote' => CurrencyRate::getQuote('VES', 'USD', 'bcv'),
            'paraleloQuote' => CurrencyRate::getQuote('VES', 'USD', 'paralelo'),
            'binanceVesQuote' => CurrencyRate::getQuote('VES', 'USD', 'binance'),
            'copQuote' => CurrencyRate::getQuote('COP', 'USD', 'official') ?? CurrencyRate::getQuote('COP', 'USD', 'binance'),
            'copBinanceQuote' => CurrencyRate::getQuote('COP', 'USD', 'binance'),
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
