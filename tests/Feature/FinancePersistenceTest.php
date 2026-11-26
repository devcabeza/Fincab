<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('creates the shared finance persistence tables', function () {
    expect(Schema::hasTable('workspaces'))->toBeTrue();
    expect(Schema::hasTable('accounts'))->toBeTrue();
    expect(Schema::hasTable('budget_periods'))->toBeTrue();
    expect(Schema::hasTable('budget_buckets'))->toBeTrue();
    expect(Schema::hasTable('debts'))->toBeTrue();
    expect(Schema::hasTable('budget_items'))->toBeTrue();
    expect(Schema::hasTable('currency_rates'))->toBeTrue();
    expect(Schema::hasTable('financial_transactions'))->toBeTrue();
    expect(Schema::hasTable('debt_payments'))->toBeTrue();
});

it('persists finance records within their workspace and budget period', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Cash',
        'currency_code' => 'USD',
        'opening_balance' => '125.50000000',
    ]);
    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);
    $bucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'essentials',
        'name' => 'Essentials',
        'allocation_percent' => '60.00',
        'priority' => 1,
    ]);
    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $bucket->id,
        'title' => 'Rent',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '500.00000000',
        'amount_q2' => '500.00000000',
        'status' => 'pending',
        'is_recurring' => true,
    ]);

    expect($workspace->accounts()->findOrFail($account->id)->opening_balance)->toBe('125.50000000');
    expect($item->budgetPeriod->is($period))->toBeTrue();
    expect($item->budgetBucket->is($bucket))->toBeTrue();
    expect($user->workspaces()->findOrFail($workspace->id)->is($workspace))->toBeTrue();
});

it('preserves original currency and USD values in the financial ledger', function () {
    $workspace = Workspace::query()->create([
        'owner_id' => User::factory()->create()->id,
        'name' => 'Business',
        'type' => 'business',
        'base_currency' => 'USD',
    ]);
    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Bank',
        'currency_code' => 'USD',
        'opening_balance' => '0',
    ]);
    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Credit card',
        'currency_code' => 'COP',
        'initial_balance' => '3206000',
        'current_balance' => '3206000',
        'annual_interest_rate' => '12.5',
        'minimum_payment' => '160300',
    ]);
    $transaction = FinancialTransaction::query()->create([
        'workspace_id' => $workspace->id,
        'account_id' => $account->id,
        'debt_id' => $debt->id,
        'transaction_type' => 'debt_payment',
        'amount_original' => '214400',
        'currency_code' => 'COP',
        'exchange_rate_to_usd' => '0.000311915159',
        'amount_usd' => '66.87461073',
        'account_amount' => '66.87461073',
        'account_currency_code' => 'USD',
        'idempotency_key' => 'mobile-payment-001',
        'occurred_at' => '2026-09-15 12:00:00',
    ]);
    $payment = DebtPayment::query()->create([
        'debt_id' => $debt->id,
        'financial_transaction_id' => $transaction->id,
        'currency_code' => 'COP',
        'principal_amount' => '192180',
        'interest_amount' => '20813',
        'fees_amount' => '1207',
        'principal_amount_usd' => '60',
        'interest_amount_usd' => '6.5',
        'fees_amount_usd' => '0.37461073',
    ]);

    expect($transaction->refresh()->amount_original)->toBe('214400.00000000');
    expect($transaction->amount_usd)->toBe('66.87461073');
    expect($transaction->account_amount)->toBe('66.87461073');
    expect($transaction->exchange_rate_to_usd)->toBe('0.000311915159');
    expect($payment->principal_amount)->toBe('192180.00000000');
    expect($payment->principal_amount_usd)->toBe('60.00000000');
    expect($payment->financialTransaction->is($transaction))->toBeTrue();
    expect($payment->debt->is($debt))->toBeTrue();
});

it('rejects finance records that connect different workspaces', function () {
    $user = User::factory()->create();
    $personal = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);
    $business = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Business',
        'type' => 'business',
        'base_currency' => 'USD',
    ]);
    $personalAccount = Account::query()->create([
        'workspace_id' => $personal->id,
        'name' => 'Personal cash',
        'currency_code' => 'USD',
        'opening_balance' => '0',
    ]);
    $businessAccount = Account::query()->create([
        'workspace_id' => $business->id,
        'name' => 'Business cash',
        'currency_code' => 'USD',
        'opening_balance' => '0',
    ]);
    $personalDebt = Debt::query()->create([
        'workspace_id' => $personal->id,
        'name' => 'Personal loan',
        'currency_code' => 'USD',
        'initial_balance' => '100',
        'current_balance' => '100',
        'minimum_payment' => '10',
    ]);
    $businessDebt = Debt::query()->create([
        'workspace_id' => $business->id,
        'name' => 'Business loan',
        'currency_code' => 'USD',
        'initial_balance' => '200',
        'current_balance' => '200',
        'minimum_payment' => '20',
    ]);
    $businessPeriod = BudgetPeriod::query()->create([
        'workspace_id' => $business->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);
    $businessBucket = BudgetBucket::query()->create([
        'workspace_id' => $business->id,
        'budget_period_id' => $businessPeriod->id,
        'key' => 'essentials',
        'name' => 'Essentials',
    ]);
    $businessItem = BudgetItem::query()->create([
        'workspace_id' => $business->id,
        'budget_period_id' => $businessPeriod->id,
        'budget_bucket_id' => $businessBucket->id,
        'title' => 'Business expense',
        'item_type' => 'expense',
    ]);

    $transactionAttributes = [
        'workspace_id' => $personal->id,
        'account_id' => $personalAccount->id,
        'transaction_type' => 'expense',
        'amount_original' => '10',
        'currency_code' => 'USD',
        'exchange_rate_to_usd' => '1',
        'amount_usd' => '10',
        'account_amount' => '10',
        'account_currency_code' => 'USD',
        'occurred_at' => '2026-09-15 12:00:00',
    ];

    expect(fn () => FinancialTransaction::query()->create([
        ...$transactionAttributes,
        'account_id' => $businessAccount->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => FinancialTransaction::query()->create([
        ...$transactionAttributes,
        'counter_account_id' => $businessAccount->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => FinancialTransaction::query()->create([
        ...$transactionAttributes,
        'debt_id' => $businessDebt->id,
    ]))->toThrow(QueryException::class);

    expect(fn () => FinancialTransaction::query()->create([
        ...$transactionAttributes,
        'budget_item_id' => $businessItem->id,
    ]))->toThrow(QueryException::class);

    $businessTransaction = FinancialTransaction::query()->create([
        'workspace_id' => $business->id,
        'account_id' => $businessAccount->id,
        'debt_id' => $businessDebt->id,
        'transaction_type' => 'debt_payment',
        'amount_original' => '10',
        'currency_code' => 'USD',
        'exchange_rate_to_usd' => '1',
        'amount_usd' => '10',
        'account_amount' => '10',
        'account_currency_code' => 'USD',
        'occurred_at' => '2026-09-15 12:00:00',
    ]);

    expect(fn () => DebtPayment::query()->create([
        'debt_id' => $personalDebt->id,
        'financial_transaction_id' => $businessTransaction->id,
        'currency_code' => 'USD',
        'principal_amount' => '10',
        'interest_amount' => '0',
        'fees_amount' => '0',
        'principal_amount_usd' => '10',
        'interest_amount_usd' => '0',
        'fees_amount_usd' => '0',
    ]))->toThrow(QueryException::class);

});
