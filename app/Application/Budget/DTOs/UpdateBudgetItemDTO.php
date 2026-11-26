<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

final readonly class UpdateBudgetItemDTO
{
    public function __construct(
        public int $budgetItemId,
        public ?string $title = null,
        public ?string $splitMode = null,
        public ?string $amountQ1 = null,
        public ?string $amountQ2 = null,
        public ?string $status = null,
        public ?bool $isRecurring = null,
        public ?int $accountId = null,
        public bool $updateAccountId = false,
        public ?string $incomeCategory = null,
        public bool $updateIncomeCategory = false,
    ) {}
}
