<?php

declare(strict_types=1);

namespace App\Application\Budget\DTOs;

use DateTimeImmutable;

final readonly class CreateBudgetItemDTO
{
    /**
     * @param  numeric-string  $amountQ1
     * @param  numeric-string  $amountQ2
     */
    public function __construct(
        public int $workspaceId,
        public int $budgetPeriodId,
        public ?int $budgetBucketId,
        public string $title,
        public string $itemType = 'expense',
        public ?string $incomeCategory = null,
        public string $splitMode = 'equal',
        public string $amountQ1 = '0.00000000',
        public string $amountQ2 = '0.00000000',
        public string $status = 'pending',
        public ?int $debtId = null,
        public ?int $accountId = null,
        public bool $isRecurring = false,
        public bool $minimumPaymentProtected = false,
        public ?DateTimeImmutable $referenceDate = null,
        public ?string $justification = null,
    ) {}
}
