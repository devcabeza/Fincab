<?php

declare(strict_types=1);

use App\Domain\Budget\Enums\DebtStrategy;
use App\Domain\Budget\Enums\ItemStatus;
use App\Domain\Budget\Enums\ItemType;
use App\Domain\Budget\Enums\SplitMode;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::query()->create([
        'owner_id' => $this->user->id,
        'name' => 'Workspace Reminders',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->period = BudgetPeriod::query()->create([
        'workspace_id' => $this->workspace->id,
        'period_start' => '2026-11-01',
        'base_currency' => 'USD',
        'debt_strategy' => DebtStrategy::Snowball->value,
        'extra_debt_payment_percent' => '20.00',
    ]);

    $this->debtBucket = BudgetBucket::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'key' => 'debts',
        'name' => 'Deudas',
        'allocation_percent' => '50.00',
        'priority' => 2,
    ]);

    $this->debt = Debt::query()->create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Préstamo Banco',
        'creditor' => 'Banco Nacional',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '800.00',
        'annual_interest_rate' => '12.00',
        'minimum_payment' => '100.00',
        'payment_frequency' => 'monthly',
        'is_active' => true,
    ]);
});

it('detects pending debt obligations and threshold alerts via budget:check-reminders', function () {
    // Add pending Q1 debt item
    BudgetItem::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'budget_bucket_id' => $this->debtBucket->id,
        'debt_id' => $this->debt->id,
        'title' => 'Cuota Préstamo',
        'item_type' => ItemType::Expense->value,
        'split_mode' => SplitMode::Equal->value,
        'amount_q1' => '50.00',
        'amount_q2' => '50.00',
        'status' => ItemStatus::Pending->value,
        'is_recurring' => true,
        'minimum_payment_protected' => true,
    ]);

    // Create an item in debt bucket that exceeds 85% allocated amount
    BudgetItem::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'budget_bucket_id' => $this->debtBucket->id,
        'title' => 'Pago Extra',
        'item_type' => ItemType::Expense->value,
        'split_mode' => SplitMode::Equal->value,
        'amount_q1' => '250.00',
        'amount_q2' => '250.00',
        'status' => ItemStatus::Paid->value,
        'is_recurring' => false,
        'minimum_payment_protected' => false,
    ]);

    $exitCode = Artisan::call('budget:check-reminders', [
        '--workspace' => (string) $this->workspace->id,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('Recordatorios de Presupuesto')
        ->and($output)->toContain('Cuota Préstamo');
});

it('creates a complete JSON backup of the financial database via budget:backup', function () {
    $tempBackupPath = storage_path('framework/testing/test_backup.json');

    if (File::exists($tempBackupPath)) {
        File::delete($tempBackupPath);
    }

    $exitCode = Artisan::call('budget:backup', [
        '--path' => $tempBackupPath,
    ]);

    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and(File::exists($tempBackupPath))->toBeTrue();

    $content = json_decode((string) File::get($tempBackupPath), true);

    expect($content)->toHaveKeys(['timestamp', 'version', 'workspaces', 'debts', 'budget_periods'])
        ->and($content['workspaces'])->not->toBeEmpty();

    File::delete($tempBackupPath);
});
