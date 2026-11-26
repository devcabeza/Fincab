<?php

declare(strict_types=1);

use App\Application\Budget\Actions\ApplyExtraordinaryIncomeAction;
use App\Application\Budget\Actions\CreateBudgetItemAction;
use App\Application\Budget\DTOs\ApplyExtraordinaryIncomeDTO;
use App\Application\Budget\DTOs\CreateBudgetItemDTO;
use App\Domain\Budget\Exceptions\DiscretionaryExpenseBlockedException;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Models\Workspace;

it('automatically amortizes debt balance and records DebtPayment when BudgetItem changes from pending to paid', function () {
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

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Credit Card',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '1000.00',
        'annual_interest_rate' => '18.00',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $debtsBucket->id,
        'debt_id' => $debt->id,
        'title' => 'Credit Card Payment',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '75.00',
        'amount_q2' => '75.00',
        'status' => 'pending',
        'minimum_payment_protected' => true,
    ]);

    // Marking as paid should trigger automatic amortization
    $item->update(['status' => 'paid']);

    expect($debt->fresh()->current_balance)->toBe('850.00000000');

    $payment = DebtPayment::query()->where('debt_id', $debt->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->principal_amount)->toBe('150.00000000')
        ->and($payment->currency_code)->toBe('USD');

    $transaction = FinancialTransaction::query()->where('budget_item_id', $item->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->amount_original)->toBe('150.00000000')
        ->and($transaction->transaction_type)->toBe('debt_payment');
});

it('reverses debt amortization when BudgetItem changes from paid back to pending', function () {
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

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Loan',
        'currency_code' => 'USD',
        'initial_balance' => '500.00',
        'current_balance' => '500.00',
        'minimum_payment' => '50.00',
        'is_active' => true,
    ]);

    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $debtsBucket->id,
        'debt_id' => $debt->id,
        'title' => 'Loan Payment',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '50.00',
        'amount_q2' => '50.00',
        'status' => 'pending',
    ]);

    $item->update(['status' => 'paid']);
    expect($debt->fresh()->current_balance)->toBe('400.00000000');

    // Reversal
    $item->update(['status' => 'pending']);
    expect($debt->fresh()->current_balance)->toBe('500.00000000');

    expect(DebtPayment::query()->where('debt_id', $debt->id)->count())->toBe(0);
});

it('is idempotent against duplicate paid updates on the same budget item', function () {
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
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Debt',
        'currency_code' => 'USD',
        'initial_balance' => '300.00',
        'current_balance' => '300.00',
        'minimum_payment' => '50.00',
        'is_active' => true,
    ]);

    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $debtsBucket->id,
        'debt_id' => $debt->id,
        'title' => 'Payment',
        'item_type' => 'expense',
        'amount_q1' => '50.00',
        'amount_q2' => '0.00',
        'status' => 'pending',
    ]);

    $item->update(['status' => 'paid']);
    expect($debt->fresh()->current_balance)->toBe('250.00000000');

    // Re-saving with status 'paid' without transition must not deduct again
    $item->update(['status' => 'paid', 'title' => 'Updated title']);
    expect($debt->fresh()->current_balance)->toBe('250.00000000');
    expect(DebtPayment::query()->where('debt_id', $debt->id)->count())->toBe(1);
});

it('blocks creation of discretionary expense after day 15 when Q1 debt is pending unless justified', function () {
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
    ]);

    $discretionaryBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'discretionary',
        'name' => 'Gastos Innecesarios',
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Pending Debt',
        'currency_code' => 'USD',
        'initial_balance' => '500.00',
        'current_balance' => '500.00',
        'is_active' => true,
    ]);

    // Active pending Q1 debt item
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $debtsBucket->id,
        'debt_id' => $debt->id,
        'title' => 'Debt Q1',
        'item_type' => 'expense',
        'amount_q1' => '50.00',
        'amount_q2' => '50.00',
        'status' => 'pending',
    ]);

    $action = app(CreateBudgetItemAction::class);

    // On day 16 with pending Q1 debt: creating discretionary expense without justification fails
    expect(fn () => $action->execute(new CreateBudgetItemDTO(
        workspaceId: $workspace->id,
        budgetPeriodId: $period->id,
        budgetBucketId: $discretionaryBucket->id,
        title: 'Video Game',
        amountQ1: '0.00',
        amountQ2: '60.00',
        referenceDate: new DateTimeImmutable('2026-09-16'),
    )))->toThrow(DiscretionaryExpenseBlockedException::class);

    // With explicit justification, it succeeds
    $created = $action->execute(new CreateBudgetItemDTO(
        workspaceId: $workspace->id,
        budgetPeriodId: $period->id,
        budgetBucketId: $discretionaryBucket->id,
        title: 'Video Game',
        amountQ1: '0.00',
        amountQ2: '60.00',
        referenceDate: new DateTimeImmutable('2026-09-16'),
        justification: 'Birthday gift budgeted in advance',
    ));

    expect($created->id)->not->toBeNull()
        ->and($created->title)->toBe('Video Game');
});

it('calculates and applies extraordinary income acceleration to top priority debt', function () {
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
    ]);

    BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'debts',
        'name' => 'Deudas',
    ]);

    // Debt 1: balance $400, interest 10%
    Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Low Interest Debt',
        'currency_code' => 'USD',
        'initial_balance' => '400.00',
        'current_balance' => '400.00',
        'annual_interest_rate' => '10.00',
        'minimum_payment' => '40.00',
        'is_active' => true,
    ]);

    // Debt 2: balance $1200, interest 24%
    $highInterestDebt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'High Interest Debt',
        'currency_code' => 'USD',
        'initial_balance' => '1200.00',
        'current_balance' => '1200.00',
        'annual_interest_rate' => '24.00',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    $action = app(ApplyExtraordinaryIncomeAction::class);
    $result = $action->execute(new ApplyExtraordinaryIncomeDTO(
        workspaceId: $workspace->id,
        budgetPeriodId: $period->id,
        incomeTitle: 'Freelance Bonus',
        incomeAmount: '1000.00',
        accelerationPercent: '60.00', // 60% of $1000 = $600
    ));

    expect($result->allocatedDebtId)->toBe($highInterestDebt->id)
        ->and($result->acceleratedAmount)->toBe('600.00000000');

    // Extraordinary payment item created for high interest debt
    $item = BudgetItem::query()
        ->where('budget_period_id', $period->id)
        ->where('debt_id', $highInterestDebt->id)
        ->where('minimum_payment_protected', false)
        ->first();

    expect($item)->not->toBeNull()
        ->and($item->amount_q1)->toBe('300.00000000')
        ->and($item->amount_q2)->toBe('300.00000000');
});
