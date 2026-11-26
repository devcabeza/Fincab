<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\CreateBudgetItemDTO;
use App\Domain\Budget\Exceptions\DiscretionaryExpenseBlockedException;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;

final class CreateBudgetItemAction
{
    public function execute(CreateBudgetItemDTO $dto): BudgetItem
    {
        if ($dto->budgetBucketId !== null) {
            /** @var BudgetBucket|null $bucket */
            $bucket = BudgetBucket::query()->find($dto->budgetBucketId);

            if ($bucket !== null && $bucket->key === 'discretionary') {
                $dayOfMonth = $dto->referenceDate !== null
                    ? (int) $dto->referenceDate->format('j')
                    : (int) date('j');

                if ($dayOfMonth > 15) {
                    $hasPendingQ1Debt = BudgetItem::query()
                        ->where('budget_period_id', $dto->budgetPeriodId)
                        ->whereNotNull('debt_id')
                        ->where('status', 'pending')
                        ->get()
                        ->contains(function (BudgetItem $item): bool {
                            return bccomp($this->toNumeric($item->amount_q1), '0', 8) > 0;
                        });

                    if ($hasPendingQ1Debt && empty(trim((string) $dto->justification))) {
                        throw DiscretionaryExpenseBlockedException::dueToPendingQ1Debts();
                    }
                }
            }
        }

        $q1 = $this->toNumeric($dto->amountQ1);
        $q2 = $this->toNumeric($dto->amountQ2);

        /** @var BudgetItem $item */
        $item = BudgetItem::query()->create([
            'workspace_id' => $dto->workspaceId,
            'budget_period_id' => $dto->budgetPeriodId,
            'budget_bucket_id' => $dto->budgetBucketId,
            'debt_id' => $dto->debtId,
            'account_id' => $dto->accountId,
            'title' => trim($dto->title),
            'item_type' => $dto->itemType,
            'income_category' => $dto->incomeCategory,
            'split_mode' => $dto->splitMode,
            'amount_q1' => $q1,
            'amount_q2' => $q2,
            'status' => $dto->status,
            'is_recurring' => $dto->isRecurring,
            'minimum_payment_protected' => $dto->minimumPaymentProtected,
        ]);

        return $item;
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
