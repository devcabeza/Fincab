<?php

declare(strict_types=1);

use App\Application\Budget\Actions\CalculateBudgetWaterfallAction;
use App\Application\Budget\Actions\CloneBudgetPeriodAction;
use App\Application\Budget\Actions\GetCashFlowSummaryAction;
use App\Application\Budget\Actions\UpdateBudgetItemAction;
use App\Application\Budget\DTOs\CalculateBudgetWaterfallDTO;
use App\Application\Budget\DTOs\CloneBudgetPeriodDTO;
use App\Application\Budget\DTOs\GetCashFlowSummaryDTO;
use App\Application\Budget\DTOs\UpdateBudgetItemDTO;
use App\Domain\Budget\Exceptions\ProtectedDebtMinimumException;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\User;
use App\Models\Workspace;

it('executes the 4-level budget waterfall allocating debt minimums and surplus accelerator', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
        'debt_strategy' => 'avalanche',
        'extra_debt_payment_percent' => '20.00',
    ]);

    $essentialsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
        'priority' => 1,
    ]);

    $debtsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'debts',
        'name' => 'Deudas',
        'priority' => 2,
    ]);

    $prioritiesBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'priorities',
        'name' => 'Prioridades / Inversión',
        'allocation_percent' => '50.00',
        'priority' => 3,
    ]);

    $digitalBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'digital_investments',
        'name' => 'Inversiones Digitales',
        'allocation_percent' => '30.00',
        'priority' => 4,
    ]);

    $discretionaryBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'discretionary',
        'name' => 'Gastos Innecesarios',
        'allocation_percent' => '20.00',
        'priority' => 5,
    ]);

    // Income: $3000
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Main Salary',
        'item_type' => 'income',
        'split_mode' => 'equal',
        'amount_q1' => '1500.00',
        'amount_q2' => '1500.00',
        'status' => 'paid',
    ]);

    // Essentials expense: $1000
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $essentialsBucket->id,
        'title' => 'Rent',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '500.00',
        'amount_q2' => '500.00',
        'status' => 'pending',
    ]);

    // Debts:
    // Debt 1: balance $500, interest 10%, minimum $100
    $debt1 = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Personal Loan',
        'currency_code' => 'USD',
        'initial_balance' => '500.00',
        'current_balance' => '500.00',
        'annual_interest_rate' => '10.00',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    // Debt 2: balance $2000, interest 25%, minimum $200
    $debt2 = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Credit Card',
        'currency_code' => 'USD',
        'initial_balance' => '2000.00',
        'current_balance' => '2000.00',
        'annual_interest_rate' => '25.00',
        'minimum_payment' => '200.00',
        'is_active' => true,
    ]);

    $action = app(CalculateBudgetWaterfallAction::class);
    $result = $action->execute(new CalculateBudgetWaterfallDTO(
        budgetPeriodId: $period->id,
    ));

    // Total income: $3000
    // Level 1: Essentials = $1000. Remaining = $2000.
    // Level 2: Minimum debts = $300 (Debt1: $100, Debt2: $200). Surplus = $1700.
    // Level 3: Extra accelerator = 20% of $1700 = $340.
    //          Strategy 'avalanche' selects Debt 2 (25% interest > 10%).
    //          Remaining surplus = $1700 - $340 = $1360.
    // Level 4:
    //   Priorities (50%): $680
    //   Digital investments (30%): $408
    //   Discretionary (20%): $272

    expect($result->totalIncome)->toBe('3000.00000000')
        ->and($result->essentialsAmount)->toBe('1000.00000000')
        ->and($result->minimumDebtsAmount)->toBe('300.00000000')
        ->and($result->acceleratorAmount)->toBe('340.00000000')
        ->and($result->acceleratorDebtId)->toBe($debt2->id)
        ->and($result->remainingSurplus)->toBe('1360.00000000')
        ->and($result->bucketLimits['priorities'])->toBe('680.00000000')
        ->and($result->bucketLimits['digital_investments'])->toBe('408.00000000')
        ->and($result->bucketLimits['discretionary'])->toBe('272.00000000');

    // Verify budget items were created for debts
    $debt1Item = BudgetItem::query()->where('budget_period_id', $period->id)
        ->where('debt_id', $debt1->id)
        ->where('minimum_payment_protected', true)
        ->first();
    expect($debt1Item)->not->toBeNull()
        ->and($debt1Item->amount_q1)->toBe('50.00000000')
        ->and($debt1Item->amount_q2)->toBe('50.00000000')
        ->and($debt1Item->minimum_payment_protected)->toBeTrue();

    $debt2Item = BudgetItem::query()->where('budget_period_id', $period->id)
        ->where('debt_id', $debt2->id)
        ->where('minimum_payment_protected', true)
        ->first();
    expect($debt2Item)->not->toBeNull()
        ->and($debt2Item->amount_q1)->toBe('100.00000000')
        ->and($debt2Item->amount_q2)->toBe('100.00000000');

    // Verify accelerator extra item was created for debt2
    $acceleratorItem = BudgetItem::query()->where('budget_period_id', $period->id)
        ->where('debt_id', $debt2->id)
        ->where('minimum_payment_protected', false)
        ->first();
    expect($acceleratorItem)->not->toBeNull()
        ->and($acceleratorItem->amount_q1)->toBe('170.00000000')
        ->and($acceleratorItem->amount_q2)->toBe('170.00000000');
});

it('sets discretionary budget cap to zero when expenses and debt minimums exceed income', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $essentialsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
        'priority' => 1,
    ]);

    BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'debts',
        'name' => 'Deudas',
        'priority' => 2,
    ]);

    BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'discretionary',
        'name' => 'Gastos Innecesarios',
        'allocation_percent' => '30.00',
        'priority' => 5,
    ]);

    // Income: $1000
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Income',
        'item_type' => 'income',
        'amount_q1' => '1000.00',
        'amount_q2' => '0.00',
    ]);

    // Essentials: $900
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $essentialsBucket->id,
        'title' => 'Essential Expense',
        'item_type' => 'expense',
        'amount_q1' => '450.00',
        'amount_q2' => '450.00',
    ]);

    // Debt minimum: $200. Total required = $1100 > $1000 income
    Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Debt',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '1000.00',
        'minimum_payment' => '200.00',
        'is_active' => true,
    ]);

    $action = app(CalculateBudgetWaterfallAction::class);
    $result = $action->execute(new CalculateBudgetWaterfallDTO(
        budgetPeriodId: $period->id,
    ));

    expect($result->surplusAfterEssentialsAndMinimums)->toBe('0.00000000')
        ->and($result->acceleratorAmount)->toBe('0.00000000')
        ->and($result->bucketLimits['discretionary'])->toBe('0.00000000');
});

it('enforces protection of debt minimum payments against downward edits', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Credit Card',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '1000.00',
        'minimum_payment' => '150.00',
        'is_active' => true,
    ]);

    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'debt_id' => $debt->id,
        'title' => 'Credit Card Minimum',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '75.00',
        'amount_q2' => '75.00',
        'minimum_payment_protected' => true,
    ]);

    $action = app(UpdateBudgetItemAction::class);

    // Attempting to edit down to $100 (< $150 minimum) must throw ProtectedDebtMinimumException
    expect(fn () => $action->execute(new UpdateBudgetItemDTO(
        budgetItemId: $item->id,
        amountQ1: '50.00',
        amountQ2: '50.00',
    )))->toThrow(ProtectedDebtMinimumException::class);

    // Editing to >= $150 succeeds
    $updated = $action->execute(new UpdateBudgetItemDTO(
        budgetItemId: $item->id,
        amountQ1: '100.00',
        amountQ2: '100.00',
    ));

    expect($updated->amount_q1)->toBe('100.00000000')
        ->and($updated->amount_q2)->toBe('100.00000000');
});

it('clones a budget period copying only recurring items and resetting statuses and variable amounts', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $sourcePeriod = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-08-01',
        'base_currency' => 'USD',
        'debt_strategy' => 'snowball',
        'extra_debt_payment_percent' => '25.00',
    ]);

    $essentialsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $sourcePeriod->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
        'priority' => 1,
    ]);

    $discretionaryBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $sourcePeriod->id,
        'key' => 'discretionary',
        'name' => 'Gastos Innecesarios',
        'allocation_percent' => '20.00',
        'priority' => 5,
    ]);

    // Item 1: Recurring essential expense (e.g. rent)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $sourcePeriod->id,
        'budget_bucket_id' => $essentialsBucket->id,
        'title' => 'Apartment Rent',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '600.00',
        'amount_q2' => '600.00',
        'status' => 'paid',
        'is_recurring' => true,
    ]);

    // Item 2: Non-recurring expense (e.g. one-off repair)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $sourcePeriod->id,
        'budget_bucket_id' => $essentialsBucket->id,
        'title' => 'Car Repair',
        'item_type' => 'expense',
        'split_mode' => 'manual',
        'amount_q1' => '250.00',
        'amount_q2' => '0.00',
        'status' => 'paid',
        'is_recurring' => false,
    ]);

    // Item 3: Recurring discretionary item (should reset amounts to 0)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $sourcePeriod->id,
        'budget_bucket_id' => $discretionaryBucket->id,
        'title' => 'Streaming Subscription',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '15.00',
        'amount_q2' => '15.00',
        'status' => 'paid',
        'is_recurring' => true,
    ]);

    $action = app(CloneBudgetPeriodAction::class);
    $newPeriod = $action->execute(new CloneBudgetPeriodDTO(
        sourcePeriodId: $sourcePeriod->id,
        targetPeriodStart: '2026-09-01',
    ));

    expect($newPeriod->period_start->format('Y-m-d'))->toBe('2026-09-01')
        ->and($newPeriod->debt_strategy)->toBe('snowball')
        ->and($newPeriod->extra_debt_payment_percent)->toBe('25.00');

    // Buckets copied
    expect($newPeriod->buckets()->count())->toBe(2);

    // Items copied: Only recurring items
    $copiedItems = $newPeriod->items()->get();
    expect($copiedItems->count())->toBe(2);

    $copiedRent = $copiedItems->firstWhere('title', 'Apartment Rent');
    expect($copiedRent)->not->toBeNull()
        ->and($copiedRent->status)->toBe('pending')
        ->and($copiedRent->amount_q1)->toBe('600.00000000')
        ->and($copiedRent->amount_q2)->toBe('600.00000000');

    $copiedStreaming = $copiedItems->firstWhere('title', 'Streaming Subscription');
    expect($copiedStreaming)->not->toBeNull()
        ->and($copiedStreaming->status)->toBe('pending')
        ->and($copiedStreaming->amount_q1)->toBe('0.00000000')
        ->and($copiedStreaming->amount_q2)->toBe('0.00000000');
});

it('calculates dynamic real savings and alerts when leisure is blocked', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $debtsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'debts',
        'name' => 'Deudas',
        'priority' => 2,
    ]);

    // Income: $2500 total, $2000 collected (paid), $500 pending
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Paid Income',
        'item_type' => 'income',
        'amount_q1' => '2000.00',
        'amount_q2' => '0.00',
        'status' => 'paid',
    ]);
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Pending Income',
        'item_type' => 'income',
        'amount_q1' => '0.00',
        'amount_q2' => '500.00',
        'status' => 'pending',
    ]);

    // Expenses:
    // Expense 1: $600 paid in Q1
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Paid Expense Q1',
        'item_type' => 'expense',
        'amount_q1' => '600.00',
        'amount_q2' => '0.00',
        'status' => 'paid',
    ]);
    // Expense 2: $400 pending in Q1
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Pending Expense Q1',
        'item_type' => 'expense',
        'amount_q1' => '400.00',
        'amount_q2' => '0.00',
        'status' => 'pending',
    ]);
    // Expense 3: $500 pending in Q2
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Pending Expense Q2',
        'item_type' => 'expense',
        'amount_q1' => '0.00',
        'amount_q2' => '500.00',
        'status' => 'pending',
    ]);

    // Debt item with pending Q1 amount
    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Loan',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '1000.00',
        'minimum_payment' => '200.00',
        'is_active' => true,
    ]);
    $debtItem = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $debtsBucket->id,
        'debt_id' => $debt->id,
        'title' => 'Loan Payment',
        'item_type' => 'expense',
        'amount_q1' => '100.00',
        'amount_q2' => '100.00',
        'status' => 'pending',
        'minimum_payment_protected' => true,
    ]);

    $action = app(GetCashFlowSummaryAction::class);

    // Total Collected Income = $2000
    // Total Expenses Paid = 600
    // Real Savings = 2000 - 600 = $1400
    // Total Committed Expenses (Paid + Pending) = 600 + 400 + 500 + 200 = 1700
    // Projected Savings = 2000 - 1700 = $300
    // Liquid needed Q1 pending = 400 + 100 = 500
    // Liquid needed Q2 pending = 500 + 100 = 600

    // On day 10 (before day 15): leisure not blocked
    $summaryBefore15 = $action->execute(new GetCashFlowSummaryDTO(
        budgetPeriodId: $period->id,
        referenceDate: new DateTimeImmutable('2026-09-10'),
    ));

    expect($summaryBefore15->totalCollectedIncome)->toBe('2000.00000000')
        ->and($summaryBefore15->totalCommittedExpenses)->toBe('1700.00000000')
        ->and($summaryBefore15->realSavings)->toBe('1400.00000000')
        ->and($summaryBefore15->projectedSavings)->toBe('300.00000000')
        ->and($summaryBefore15->liquidNeededQ1)->toBe('500.00000000')
        ->and($summaryBefore15->liquidNeededQ2)->toBe('600.00000000')
        ->and($summaryBefore15->isLeisureBlocked)->toBeFalse();

    // On day 16 (after day 15) with pending Q1 debt: leisure IS blocked
    $summaryAfter15 = $action->execute(new GetCashFlowSummaryDTO(
        budgetPeriodId: $period->id,
        referenceDate: new DateTimeImmutable('2026-09-16'),
    ));
    expect($summaryAfter15->isLeisureBlocked)->toBeTrue();

    // If debt Q1 is paid, leisure is NOT blocked after 15th
    $debtItem->update([
        'status' => 'paid',
    ]);

    $summaryAfterDebtPaid = $action->execute(new GetCashFlowSummaryDTO(
        budgetPeriodId: $period->id,
        referenceDate: new DateTimeImmutable('2026-09-16'),
    ));
    expect($summaryAfterDebtPaid->isLeisureBlocked)->toBeFalse();
});
