<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Domain\Budget\Enums\ItemStatus;
use App\Domain\Budget\Enums\ItemType;
use App\Domain\Budget\Enums\SplitMode;
use App\Infrastructure\Mobile\LocalSyncManager;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class QuickExpenseScreen extends NativeComponent
{
    public int $workspaceId = 0;

    public ?int $periodId = null;

    public string $title = '';

    public string $amount = '';

    public string $currency = 'USD';

    public string $exchangeRate = '1.000000';

    public string $bucketKey = 'essentials';

    public int $quincena = 1;

    public bool $isDebt = false;

    public bool $isRecurring = false;

    public ?int $debtId = null;

    public string $feedbackMessage = '';

    public string $errorMessage = '';

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

    public function saveExpense(): void
    {
        $this->errorMessage = '';
        $this->feedbackMessage = '';

        if (trim($this->title) === '') {
            $this->errorMessage = 'El título del gasto es requerido.';

            return;
        }

        if (! is_numeric($this->amount) || (float) $this->amount <= 0) {
            $this->errorMessage = 'El monto debe ser un número positivo.';

            return;
        }

        $period = BudgetPeriod::query()->find($this->periodId);
        if ($period === null) {
            $this->errorMessage = 'No hay un período presupuestario activo.';

            return;
        }

        // Check if bucket exists, or create default for key
        $bucket = BudgetBucket::query()
            ->where('budget_period_id', $period->id)
            ->where('key', $this->bucketKey)
            ->first();

        if ($bucket === null) {
            $bucket = BudgetBucket::query()->create([
                'workspace_id' => $this->workspaceId,
                'budget_period_id' => $period->id,
                'key' => $this->bucketKey,
                'name' => ucfirst($this->bucketKey),
                'priority' => 3,
            ]);
        }

        $finalAmount = $this->amount;
        if ($this->currency !== 'USD' && is_numeric($this->exchangeRate) && (float) $this->exchangeRate > 0) {
            if ((float) $this->exchangeRate > 1.0) {
                $finalAmount = bcdiv((string) $this->amount, (string) $this->exchangeRate, 2);
            } else {
                $finalAmount = bcmul((string) $this->amount, (string) $this->exchangeRate, 2);
            }
        }

        $amountQ1 = $this->quincena === 1 ? $finalAmount : '0.00';
        $amountQ2 = $this->quincena === 2 ? $finalAmount : '0.00';

        $resolvedDebtId = null;
        if ($this->isDebt) {
            $resolvedDebtId = $this->debtId;
            if ($resolvedDebtId === null) {
                $debt = Debt::query()
                    ->where('workspace_id', $this->workspaceId)
                    ->where('is_active', true)
                    ->first();
                $resolvedDebtId = $debt?->id;
            }
        }

        $item = BudgetItem::query()->create([
            'workspace_id' => $this->workspaceId,
            'budget_period_id' => $period->id,
            'budget_bucket_id' => $bucket->id,
            'debt_id' => $resolvedDebtId,
            'title' => $this->title,
            'item_type' => ItemType::Expense->value,
            'split_mode' => SplitMode::Manual->value,
            'amount_q1' => $amountQ1,
            'amount_q2' => $amountQ2,
            'status' => ItemStatus::Pending->value,
            'is_recurring' => $this->isRecurring,
            'minimum_payment_protected' => $this->isDebt,
        ]);

        // Queue local sync mutation
        app(LocalSyncManager::class)->queueMutation(
            workspaceId: $this->workspaceId,
            entityType: 'budget_item',
            action: 'create',
            payload: [
                'id' => $item->id,
                'title' => $item->title,
                'amount_q1' => $item->amount_q1,
                'amount_q2' => $item->amount_q2,
                'bucket_key' => $this->bucketKey,
                'status' => $item->status,
            ]
        );

        $this->feedbackMessage = '¡Gasto registrado exitosamente!';
        $this->title = '';
        $this->amount = '';
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
        $debts = Debt::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('is_active', true)
            ->get();

        return view('native.quick-expense-screen', [
            'debts' => $debts,
        ]);
    }
}
