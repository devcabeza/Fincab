<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\UpdateBudgetItemDTO;
use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Exceptions\ProtectedDebtMinimumException;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Models\BudgetItem;
use App\Models\Debt;

final class UpdateBudgetItemAction
{
    public function __construct(
        private readonly QuincenaCalculator $quincenaCalculator = new QuincenaCalculator,
    ) {}

    public function execute(UpdateBudgetItemDTO $dto): BudgetItem
    {
        $item = BudgetItem::query()->findOrFail($dto->budgetItemId);

        $newQ1 = $dto->amountQ1 !== null ? $this->toNumeric($dto->amountQ1) : $this->toNumeric($item->amount_q1);
        $newQ2 = $dto->amountQ2 !== null ? $this->toNumeric($dto->amountQ2) : $this->toNumeric($item->amount_q2);

        if ($item->minimum_payment_protected && $item->debt_id !== null) {
            /** @var Debt $debt */
            $debt = Debt::query()->findOrFail($item->debt_id);
            $minimum = $this->toNumeric($debt->minimum_payment);

            if (! $this->quincenaCalculator->meetsMinimum($newQ1, $newQ2, $minimum)) {
                $attempted = $this->quincenaCalculator->sum($newQ1, $newQ2);
                throw ProtectedDebtMinimumException::cannotReduceBelowMinimum($minimum, $attempted);
            }
        }

        $splitMode = $dto->splitMode !== null
            ? (SplitMode::tryFrom($dto->splitMode) ?? SplitMode::Manual)
            : (SplitMode::tryFrom((string) $item->split_mode) ?? SplitMode::Manual);

        $updates = [
            'amount_q1' => $newQ1,
            'amount_q2' => $newQ2,
            'split_mode' => $splitMode->value,
        ];

        if ($dto->title !== null) {
            $updates['title'] = trim($dto->title);
        }

        if ($dto->status !== null) {
            $updates['status'] = $dto->status;
        }

        if ($dto->isRecurring !== null) {
            $updates['is_recurring'] = $dto->isRecurring;
        }

        if ($dto->updateAccountId) {
            $updates['account_id'] = $dto->accountId;
        }

        if ($dto->updateIncomeCategory) {
            $updates['income_category'] = $dto->incomeCategory;
        }

        $item->update($updates);

        return $item->refresh();
    }

    /**
     * @return numeric-string
     */
    private function toNumeric(mixed $value): string
    {
        return is_numeric($value) ? (string) $value : '0.00000000';
    }
}
