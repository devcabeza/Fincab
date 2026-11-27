<?php

declare(strict_types=1);

use App\Application\Goal\Actions\CreateGoalAction;
use App\Application\Goal\DTOs\CreateGoalDTO;
use App\Domain\Goal\Enums\GoalType;
use App\Livewire\Goals\GoalsDashboard;
use App\Models\Debt;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::query()->create([
        'owner_id' => $this->user->id,
        'name' => 'Personal Workspace',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);
});

test('goals dashboard renders successfully for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->assertOk()
        ->assertSee('Sistema de Objetivos Financieros')
        ->assertSee('Total Acumulado en Sobres')
        ->assertSee('No tienes metas de ahorro registradas');
});

test('can create a target amount goal from livewire modal', function () {
    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->set('name', 'MacBook Pro M4')
        ->set('type', 'target_amount')
        ->set('targetAmount', '3000.00')
        ->set('plannedMonthlyQuota', '300.00')
        ->set('startDate', '2026-01-01')
        ->set('targetDate', '2026-11-01')
        ->call('createGoal')
        ->assertHasNoErrors()
        ->assertSee('MacBook Pro M4')
        ->assertSee('3,000.00');

    $this->assertDatabaseHas('goals', [
        'workspace_id' => $this->workspace->id,
        'name' => 'MacBook Pro M4',
        'type' => 'target_amount',
    ]);
});

test('validates required fields when creating a goal', function () {
    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->set('name', '')
        ->set('targetAmount', '')
        ->set('targetDate', '')
        ->call('createGoal')
        ->assertHasErrors(['name', 'targetAmount', 'targetDate']);
});

test('what-if playground updates reactively when simulated monthly quota changes', function () {
    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop Gamer',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00',
        plannedMonthlyQuota: '300.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->call('selectGoalForSimulation', $goal->id)
        ->set('simulatedMonthly', '100.00')
        ->assertSee('Simulador Interactivo')
        ->assertSee('What-If')
        ->assertSee('Retraso de');
});

test('shows debt shield warning and blocks contribution when active debts exist', function () {
    Debt::query()->create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Préstamo Bancario',
        'currency_code' => 'USD',
        'initial_balance' => '5000.00',
        'current_balance' => '4000.00',
        'annual_interest_rate' => '18.00',
        'minimum_payment' => '250.00',
        'payment_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Viaje a Japón',
        type: GoalType::TargetAmount,
        targetAmount: '2000.00',
        plannedMonthlyQuota: '200.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->assertSee('ESCUDO DE SALUD FINANCIERA ACTIVO')
        ->set('selectedGoalId', $goal->id)
        ->set('contributionAmount', '150.00')
        ->call('contribute')
        ->assertSee('Los aportes a metas de ahorro están bloqueados');

    expect((float) $goal->fresh()->current_amount)->toBe(0.0);
});

test('successfully saves contribution and updates goal balance when debts are clear', function () {
    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Fondo de Emergencia',
        type: GoalType::TargetAmount,
        targetAmount: '1000.00',
        plannedMonthlyQuota: '100.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    Livewire::actingAs($this->user)
        ->test(GoalsDashboard::class)
        ->set('selectedGoalId', $goal->id)
        ->set('contributionAmount', '250.00')
        ->call('contribute')
        ->assertSee('Aporte registrado correctamente en tu sobre virtual');

    expect((float) $goal->fresh()->current_amount)->toBe(250.0);
});
