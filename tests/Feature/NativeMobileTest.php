<?php

declare(strict_types=1);

use App\Domain\Budget\Enums\DebtStrategy;
use App\Domain\Budget\Enums\ItemStatus;
use App\Domain\Budget\Enums\ItemType;
use App\Domain\Budget\Enums\SplitMode;
use App\Infrastructure\Mobile\LocalSyncManager;
use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\SyncMutation;
use App\Models\User;
use App\Models\Workspace;
use App\NativeComponents\MobileDashboardScreen;
use App\NativeComponents\MobileDebtScreen;
use App\NativeComponents\QuickExpenseScreen;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'native_mobile@example.com']);
    $this->workspace = Workspace::query()->create([
        'owner_id' => $this->user->id,
        'name' => 'Personal Móvil',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->account = Account::query()->create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Efectivo USD',
        'currency_code' => 'USD',
        'opening_balance' => '1500.00',
        'is_active' => true,
    ]);

    $this->period = BudgetPeriod::query()->create([
        'workspace_id' => $this->workspace->id,
        'period_start' => '2026-11-01',
        'base_currency' => 'USD',
        'debt_strategy' => DebtStrategy::Avalanche->value,
        'extra_debt_payment_percent' => '20.00',
    ]);

    $this->essentialBucket = BudgetBucket::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
        'priority' => 1,
    ]);

    $this->debtBucket = BudgetBucket::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'key' => 'debts',
        'name' => 'Deudas',
        'priority' => 2,
    ]);

    $this->debt = Debt::query()->create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Tarjeta Visa Móvil',
        'creditor' => 'Banco Móvil',
        'currency_code' => 'USD',
        'initial_balance' => '2000.00',
        'current_balance' => '1200.00',
        'annual_interest_rate' => '18.50',
        'minimum_payment' => '150.00',
        'payment_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $this->item = BudgetItem::query()->create([
        'workspace_id' => $this->workspace->id,
        'budget_period_id' => $this->period->id,
        'budget_bucket_id' => $this->debtBucket->id,
        'debt_id' => $this->debt->id,
        'title' => 'Pago Mínimo Visa',
        'item_type' => ItemType::Expense->value,
        'split_mode' => SplitMode::Equal->value,
        'amount_q1' => '75.00',
        'amount_q2' => '75.00',
        'status' => ItemStatus::Pending->value,
        'is_recurring' => true,
        'minimum_payment_protected' => true,
    ]);
});

it('renders mobile dashboard screen and displays debt thermometer and KPIs', function () {
    Native::test(MobileDashboardScreen::class, [
        'workspaceId' => $this->workspace->id,
        'periodId' => $this->period->id,
    ])
        ->assertSee('Personal Móvil')
        ->assertSee('Termómetro de Deudas')
        ->assertSee('1,200.00')
        ->assertSee('Saldo Líquido')
        ->assertSee('1,500.00');
});

it('allows marking a pending item as paid from the mobile dashboard', function () {
    $screen = Native::test(MobileDashboardScreen::class, [
        'workspaceId' => $this->workspace->id,
        'periodId' => $this->period->id,
    ]);

    $screen->call('markAsPaid', $this->item->id);

    expect($this->item->fresh()->status)->toBe(ItemStatus::Paid->value);
    expect((float) $this->debt->fresh()->current_balance)->toBe(1050.00);
});

it('records quick expense on mobile and queues sync mutation', function () {
    $screen = Native::test(QuickExpenseScreen::class, [
        'workspaceId' => $this->workspace->id,
        'periodId' => $this->period->id,
    ]);

    $screen->set('title', 'Almuerzo Rápido')
        ->set('amount', '25.00')
        ->set('bucketKey', 'essentials')
        ->set('quincena', 1)
        ->call('saveExpense');

    $newItem = BudgetItem::where('workspace_id', $this->workspace->id)
        ->where('title', 'Almuerzo Rápido')
        ->first();

    expect($newItem)->not->toBeNull()
        ->and((float) $newItem->amount_q1)->toBe(25.00);

    $mutation = SyncMutation::where('workspace_id', $this->workspace->id)
        ->where('entity_type', 'budget_item')
        ->first();

    expect($mutation)->not->toBeNull()
        ->and($mutation->mutation_id)->not->toBeEmpty();
});

it('switches debt strategy from mobile debt screen', function () {
    $screen = Native::test(MobileDebtScreen::class, [
        'workspaceId' => $this->workspace->id,
        'periodId' => $this->period->id,
    ]);

    expect($screen->get('activeStrategy'))->toBe(DebtStrategy::Avalanche->value);

    $screen->call('setStrategy', DebtStrategy::Snowball->value);

    expect($screen->get('activeStrategy'))->toBe(DebtStrategy::Snowball->value);
    expect($this->period->fresh()->debt_strategy)->toBe(DebtStrategy::Snowball->value);
});

it('manages local sync mutations with LocalSyncManager', function () {
    $syncManager = app(LocalSyncManager::class);

    $mutation = $syncManager->queueMutation(
        workspaceId: $this->workspace->id,
        entityType: 'budget_item',
        action: 'create',
        payload: ['id' => 99, 'title' => 'Offline Expense', 'amount' => '40.00']
    );

    expect($mutation->id)->not->toBeNull()
        ->and($mutation->mutation_id)->not->toBeEmpty();

    $pending = $syncManager->getPendingMutations($this->workspace->id);
    expect($pending)->toHaveCount(1)
        ->and($pending->first()->entity_type)->toBe('budget_item');
});
