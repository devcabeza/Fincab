<?php

declare(strict_types=1);

use App\Livewire\Budget\AccountsAndRatesView;
use App\Livewire\Budget\BudgetSheetView;
use App\Livewire\Budget\DashboardOverview;
use App\Livewire\Budget\DebtManagerView;
use App\Livewire\Budget\GlobalLiquidityBanner;
use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

it('renders DashboardOverview with KPIs, thermometer, and bucket progress', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Finanzas Personales',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Efectivo',
        'currency_code' => 'USD',
        'opening_balance' => '1200.00',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
        'debt_strategy' => 'avalanche',
    ]);

    $essentialsBucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
        'priority' => 1,
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Tarjeta Visa',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '800.00',
        'annual_interest_rate' => '19.50',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Sueldo',
        'item_type' => 'income',
        'amount_q1' => '1500.00',
        'amount_q2' => '1500.00',
        'status' => 'paid',
    ]);

    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'budget_bucket_id' => $essentialsBucket->id,
        'title' => 'Alquiler',
        'item_type' => 'expense',
        'amount_q1' => '400.00',
        'amount_q2' => '400.00',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(DashboardOverview::class)
        ->assertOk()
        ->assertSee('Finanzas Personales')
        ->assertSee('Termómetro de Deuda')
        ->assertSee('Tarjeta Visa')
        ->assertSee('Gastos Esenciales');
});

it('interacts with BudgetSheetView allowing 1-click status toggle and item creation', function () {
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
        'name' => 'Préstamo',
        'currency_code' => 'USD',
        'initial_balance' => '500.00',
        'current_balance' => '500.00',
        'minimum_payment' => '50.00',
        'is_active' => true,
    ]);

    $item = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'debt_id' => $debt->id,
        'title' => 'Cuota Préstamo',
        'item_type' => 'expense',
        'split_mode' => 'equal',
        'amount_q1' => '50.00',
        'amount_q2' => '50.00',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->assertSee('Cuota Préstamo')
        ->call('toggleStatus', $item->id);

    // After 1-click toggle, status should be paid and debt balance amortized to 400
    expect($item->fresh()->status)->toBe('paid')
        ->and($debt->fresh()->current_balance)->toBe('400.00000000');
});

it('updates debt strategy and simulates extra payment in DebtManagerView', function () {
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

    Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Deuda 1',
        'currency_code' => 'USD',
        'initial_balance' => '1000.00',
        'current_balance' => '1000.00',
        'annual_interest_rate' => '22.00',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(DebtManagerView::class)
        ->assertOk()
        ->assertSee('Deuda 1')
        ->call('switchStrategy', 'snowball');

    expect($period->fresh()->debt_strategy)->toBe('snowball');
});

it('creates and deletes debt in DebtManagerView using single total amount', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    Livewire::test(DebtManagerView::class)
        ->assertOk()
        ->call('openDebtModal')
        ->assertSet('showDebtModal', true)
        ->set('debtName', 'Deuda Leonel')
        ->set('creditor', 'Leonel Medina')
        ->set('totalAmount', '500.00')
        ->call('createDebt')
        ->assertSet('showDebtModal', false);

    $debt = Debt::query()->where('name', 'Deuda Leonel')->first();
    expect($debt)->not->toBeNull()
        ->and($debt->creditor)->toBe('Leonel Medina')
        ->and((float) $debt->initial_balance)->toBe(500.0)
        ->and((float) $debt->current_balance)->toBe(500.0)
        ->and((float) $debt->annual_interest_rate)->toBe(0.0)
        ->and((float) $debt->minimum_payment)->toBe(0.0);

    Livewire::test(DebtManagerView::class)
        ->call('deleteDebt', $debt->id);

    expect(Debt::query()->where('id', $debt->id)->exists())->toBeFalse();
});

it('manages accounts in AccountsAndRatesView', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    Livewire::test(AccountsAndRatesView::class)
        ->assertOk()
        ->call('openCreateAccountModal')
        ->assertSet('showCreateAccountModal', true)
        ->set('newAccountName', 'Banesco USD')
        ->set('newAccountCurrency', 'USD')
        ->set('newAccountBalance', '350.00')
        ->call('createAccount')
        ->assertSet('showCreateAccountModal', false);

    expect(Account::query()->where('name', 'Banesco USD')->exists())->toBeTrue();
});

it('allows editing and deleting accounts in AccountsAndRatesView', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Binanace',
        'currency_code' => 'USD',
        'opening_balance' => '100.00',
    ]);

    $this->actingAs($user);

    Livewire::test(AccountsAndRatesView::class)
        ->assertOk()
        ->assertSee('Binanace')
        ->call('openEditAccountModal', $account->id)
        ->assertSet('editAccountName', 'Binanace')
        ->set('editAccountName', 'Binance')
        ->set('editAccountBalance', '150.00')
        ->call('updateAccount')
        ->assertSee('Binance');

    expect($account->fresh()->name)->toBe('Binance')
        ->and((float) $account->fresh()->opening_balance)->toBe(150.0);

    // Delete account
    Livewire::test(AccountsAndRatesView::class)
        ->call('deleteAccount', $account->id);

    expect(Account::query()->find($account->id))->toBeNull();
});

it('creates, edits, and filters recurring expenses in BudgetSheetView', function () {
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

    $this->actingAs($user);

    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->call('openCreateModal')
        ->set('title', 'Suscripción Contabo VPS')
        ->set('amountQ1', '25.00')
        ->set('amountQ2', '0.00')
        ->set('isRecurring', true)
        ->call('saveItem')
        ->assertSee('Suscripción Contabo VPS')
        ->assertSee('Recurrente');

    $item = BudgetItem::query()->where('title', 'Suscripción Contabo VPS')->first();
    expect($item)->not->toBeNull()
        ->and($item->is_recurring)->toBeTrue();

    // Test recurring filter
    Livewire::test(BudgetSheetView::class)
        ->set('recurringFilter', 'recurring')
        ->assertSee('Suscripción Contabo VPS');
});

it('automatically divides total amount biweekly and supports preset distribution buttons', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    // 1. Entering total amount in equal mode splits 50/50 automatically
    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->call('openCreateModal')
        ->set('totalAmount', '50.00')
        ->assertSet('amountQ1', '25.00')
        ->assertSet('amountQ2', '25.00')
        // 2. Switching to 100% in Q1
        ->call('setDistribution', 'q1_only')
        ->assertSet('amountQ1', '50.00')
        ->assertSet('amountQ2', '0.00')
        // 3. Switching to 100% in Q2
        ->call('setDistribution', 'q2_only')
        ->assertSet('amountQ1', '0.00')
        ->assertSet('amountQ2', '50.00')
        // 4. Back to equal mode
        ->call('setDistribution', 'equal')
        ->assertSet('amountQ1', '25.00')
        ->assertSet('amountQ2', '25.00')
        ->set('title', 'Contabo VPS')
        ->set('isRecurring', true)
        ->call('saveItem')
        ->assertSee('Contabo VPS');

    $item = BudgetItem::query()->where('title', 'Contabo VPS')->first();
    expect($item)->not->toBeNull()
        ->and((float) $item->amount_q1)->toBe(25.0)
        ->and((float) $item->amount_q2)->toBe(25.0)
        ->and($item->is_recurring)->toBeTrue();
});

it('links budget items to accounts, updates account balance and ledger on payment, and filters ledger by account', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Binance',
        'currency_code' => 'USD',
        'opening_balance' => '500.00',
        'is_active' => true,
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    // 1. Create a budget item linked to Binance account
    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->call('openCreateModal')
        ->set('title', 'Contabo VPS Servidor')
        ->set('accountId', $account->id)
        ->set('totalAmount', '30.00')
        ->set('isRecurring', true)
        ->call('saveItem');

    $item = BudgetItem::query()->where('title', 'Contabo VPS Servidor')->first();
    expect($item)->not->toBeNull()
        ->and($item->account_id)->toBe($account->id)
        ->and($item->status)->toBe('pending');

    // Account balance should remain unchanged while pending
    expect((float) $account->fresh()->opening_balance)->toBe(500.0);

    // 2. Mark item as paid -> should deduct from Binance and create FinancialTransaction
    Livewire::test(BudgetSheetView::class)
        ->call('toggleStatus', $item->id);

    expect($item->fresh()->status)->toBe('paid')
        ->and((float) $account->fresh()->opening_balance)->toBe(470.0);

    $tx = FinancialTransaction::query()
        ->where('budget_item_id', $item->id)
        ->first();

    expect($tx)->not->toBeNull()
        ->and($tx->account_id)->toBe($account->id)
        ->and($tx->transaction_type)->toBe('expense')
        ->and((float) $tx->amount_original)->toBe(30.0);

    // 3. Verify AccountsAndRatesView displays the transaction and filters by account
    Livewire::test(AccountsAndRatesView::class)
        ->assertOk()
        ->assertSee('Contabo VPS Servidor')
        ->assertSee('-$30.00')
        ->set('filterAccountId', $account->id)
        ->assertSee('Contabo VPS Servidor');

    // 4. Revert status back to pending -> restores account balance and removes transaction
    Livewire::test(BudgetSheetView::class)
        ->call('toggleStatus', $item->id);

    expect($item->fresh()->status)->toBe('pending')
        ->and((float) $account->fresh()->opening_balance)->toBe(500.0)
        ->and(FinancialTransaction::query()->where('budget_item_id', $item->id)->exists())->toBeFalse();
});

it('amortizes debt, updates account balance, and records payment history when paying a debt-linked budget item', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-11-01',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Binance USDT',
        'currency_code' => 'USD',
        'opening_balance' => '500.00',
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Deuda a Leonel',
        'creditor' => 'Leonel Medina',
        'initial_balance' => '80.00',
        'current_balance' => '80.00',
        'annual_interest_rate' => '0.00',
        'minimum_payment' => '0.00',
        'currency_code' => 'USD',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // 1. Create a budget expense item linked to Debt and Account for $40
    Livewire::test(BudgetSheetView::class)
        ->call('openCreateModal')
        ->set('title', 'Abono Leonel')
        ->set('accountId', $account->id)
        ->set('debtId', $debt->id)
        ->set('totalAmount', '40.00')
        ->call('saveItem');

    $item = BudgetItem::query()->where('title', 'Abono Leonel')->first();
    expect($item)->not->toBeNull()
        ->and($item->debt_id)->toBe($debt->id)
        ->and($item->account_id)->toBe($account->id)
        ->and($item->status)->toBe('pending');

    // While pending, debt and account balances remain at 80 and 500
    expect((float) $debt->fresh()->current_balance)->toBe(80.0)
        ->and((float) $account->fresh()->opening_balance)->toBe(500.0);

    // 2. Mark item as Paid
    Livewire::test(BudgetSheetView::class)
        ->call('toggleStatus', $item->id);

    // Debt amortized from 80 -> 40, Account debited from 500 -> 460
    expect($item->fresh()->status)->toBe('paid')
        ->and((float) $debt->fresh()->current_balance)->toBe(40.0)
        ->and((float) $account->fresh()->opening_balance)->toBe(460.0);

    // Verify DebtPayment record created
    $payment = DebtPayment::query()->where('debt_id', $debt->id)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->principal_amount)->toBe(40.0);

    // Verify DebtManagerView displays the updated debt balance and payment history
    Livewire::test(DebtManagerView::class)
        ->assertOk()
        ->assertSee('Deuda a Leonel')
        ->assertSee('$40.00');

    // 3. Reverting back to Pending restores both debt and account balances
    Livewire::test(BudgetSheetView::class)
        ->call('toggleStatus', $item->id);

    expect($item->fresh()->status)->toBe('pending')
        ->and((float) $debt->fresh()->current_balance)->toBe(80.0)
        ->and((float) $account->fresh()->opening_balance)->toBe(500.0)
        ->and(DebtPayment::query()->where('debt_id', $debt->id)->exists())->toBeFalse();
});

it('manages 3 manual income categories and filters items by type in BudgetSheetView', function () {
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

    $this->actingAs($user);

    // 1. Create a Salary income item
    Livewire::test(BudgetSheetView::class)
        ->call('openCreateModal')
        ->set('title', 'Sueldo Empresa Tech')
        ->set('itemType', 'income')
        ->set('incomeCategory', BudgetItem::INCOME_CATEGORY_SALARY)
        ->set('totalAmount', '1200.00')
        ->call('saveItem');

    // 2. Create a Side Projects income item
    Livewire::test(BudgetSheetView::class)
        ->call('openCreateModal')
        ->set('title', 'Proyecto Web Freelance')
        ->set('itemType', 'income')
        ->set('incomeCategory', BudgetItem::INCOME_CATEGORY_SIDE_PROJECTS)
        ->set('totalAmount', '450.00')
        ->call('saveItem');

    // 3. Create an Investments income item
    Livewire::test(BudgetSheetView::class)
        ->call('openCreateModal')
        ->set('title', 'Dividendos ETFs')
        ->set('itemType', 'income')
        ->set('incomeCategory', BudgetItem::INCOME_CATEGORY_INVESTMENTS)
        ->set('totalAmount', '80.00')
        ->call('saveItem');

    // 4. Create an Expense item
    Livewire::test(BudgetSheetView::class)
        ->call('openCreateModal')
        ->set('title', 'Internet Fibra')
        ->set('itemType', 'expense')
        ->set('totalAmount', '50.00')
        ->call('saveItem');

    // Verify database persistence of income categories
    expect(BudgetItem::query()->where('title', 'Sueldo Empresa Tech')->value('income_category'))
        ->toBe(BudgetItem::INCOME_CATEGORY_SALARY)
        ->and(BudgetItem::query()->where('title', 'Proyecto Web Freelance')->value('income_category'))
        ->toBe(BudgetItem::INCOME_CATEGORY_SIDE_PROJECTS)
        ->and(BudgetItem::query()->where('title', 'Dividendos ETFs')->value('income_category'))
        ->toBe(BudgetItem::INCOME_CATEGORY_INVESTMENTS)
        ->and(BudgetItem::query()->where('title', 'Internet Fibra')->value('income_category'))
        ->toBeNull();

    // Verify rendering of category badges and amounts in BudgetSheetView
    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->assertSee('Sueldo Empresa Tech')
        ->assertSee('Sueldo')
        ->assertSee('Proyecto Web Freelance')
        ->assertSee('Side Project')
        ->assertSee('Dividendos ETFs')
        ->assertSee('Inversión')
        ->assertSee('Internet Fibra')
        // Test filtering by type: expenses only
        ->set('typeFilter', 'expense')
        ->assertSee('Internet Fibra')
        ->assertDontSee('Sueldo Empresa Tech')
        ->assertDontSee('Proyecto Web Freelance')
        // Test filtering by type: income only
        ->set('typeFilter', 'income')
        ->assertSee('Sueldo Empresa Tech')
        ->assertSee('Proyecto Web Freelance')
        ->assertSee('Dividendos ETFs')
        ->assertDontSee('Internet Fibra');
});

it('calculates 4-pillar liquidity metrics and renders GlobalLiquidityBanner with modal', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    // Create an account with opening balance representing initial savings
    Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Banesco Panamá',
        'currency_code' => 'USD',
        'opening_balance' => '500.00',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    // Pillar 1: Sueldo ($1,000)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Sueldo',
        'item_type' => 'income',
        'income_category' => BudgetItem::INCOME_CATEGORY_SALARY,
        'amount_q1' => '500.00',
        'amount_q2' => '500.00',
        'status' => 'paid',
    ]);

    // Pillar 2: Side Projects ($300)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Landing Page Client',
        'item_type' => 'income',
        'income_category' => BudgetItem::INCOME_CATEGORY_SIDE_PROJECTS,
        'amount_q1' => '150.00',
        'amount_q2' => '150.00',
        'status' => 'paid',
    ]);

    // Pillar 3: Inversiones ($100)
    BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Staking Crypto',
        'item_type' => 'income',
        'income_category' => BudgetItem::INCOME_CATEGORY_INVESTMENTS,
        'amount_q1' => '50.00',
        'amount_q2' => '50.00',
        'status' => 'paid',
    ]);

    // Expense: Gastos del Mes ($400) - initially pending
    $expense = BudgetItem::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'title' => 'Servicios y Comida',
        'item_type' => 'expense',
        'amount_q1' => '200.00',
        'amount_q2' => '200.00',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    // 1. While expense is pending, it must NOT be deducted from liquidity
    // Net Liquidity = Total Inflows (1000 + 300 + 100 + 500 = 1900) - Paid Expenses (0) = $1,900.00
    Livewire::test(GlobalLiquidityBanner::class)
        ->assertOk()
        ->assertSee('Liquidez y Capital Disponible')
        ->assertSee('$1,900.00')
        ->assertSee('Entradas:')
        ->assertSee('$1,900.00')
        ->assertSee('Gastos del Mes:')
        ->assertSee('-$0.00')
        // Test opening the breakdown modal: pending expense is displayed as commitment without deducting
        ->call('openBreakdownModal')
        ->assertSet('showBreakdownModal', true)
        ->assertSee('Desglose de Liquidez y Capital')
        ->assertSee('Sueldo')
        ->assertSee('Remuneración Mensual')
        ->assertSee('$1,000.00')
        ->assertSee('Side Projects')
        ->assertSee('$300.00')
        ->assertSee('Inversiones')
        ->assertSee('$100.00')
        ->assertSee('Ahorro Mes Anterior')
        ->assertSee('$500.00')
        ->assertSee('Deducciones (Gastos del Mes)')
        ->assertSee('Compromisos Pendientes')
        ->assertSee('$400.00')
        ->call('closeBreakdownModal')
        ->assertSet('showBreakdownModal', false);

    // 2. When expense is marked as paid, it is reactively deducted from liquidity
    $expense->update(['status' => 'paid']);

    // Net Liquidity = Total Inflows ($1,900) - Paid Expenses ($400) = $1,500.00
    Livewire::test(GlobalLiquidityBanner::class)
        ->assertOk()
        ->assertSee('$1,500.00')
        ->assertSee('-$400.00')
        ->call('openBreakdownModal')
        ->assertSee('Gastos Pagados (Deducidos)')
        ->assertSee('-$400.00');
});
