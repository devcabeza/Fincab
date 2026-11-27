<?php

declare(strict_types=1);

use App\Application\Goal\Actions\ContributeToGoalAction;
use App\Application\Goal\Actions\CreateGoalAction;
use App\Application\Goal\Actions\GetGoalsOverviewAction;
use App\Application\Goal\Actions\SimulateGoalScenarioAction;
use App\Application\Goal\DTOs\ContributeToGoalDTO;
use App\Application\Goal\DTOs\CreateGoalDTO;
use App\Application\Goal\DTOs\SimulateGoalDTO;
use App\Domain\Goal\Enums\GoalStatus;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Exceptions\GoalContributionBlockedByDebtException;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::query()->create([
        'owner_id' => $this->user->id,
        'name' => 'Test Workspace',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);
});

test('creates a target amount sinking fund goal successfully', function () {
    $action = app(CreateGoalAction::class);

    $goal = $action->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop nueva M4',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00000000',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
        currencyCode: 'USD',
        notes: 'Para desarrollo de software',
    ));

    expect($goal)->toBeInstanceOf(Goal::class)
        ->and($goal->name)->toBe('Laptop nueva M4')
        ->and($goal->type)->toBe(GoalType::TargetAmount)
        ->and($goal->status)->toBe(GoalStatus::Active)
        ->and((float) $goal->target_amount)->toBe(3000.0)
        ->and((float) $goal->current_amount)->toBe(0.0)
        ->and($goal->projected_date)->not->toBeNull();
});

test('creates a monthly revenue goal successfully', function () {
    $action = app(CreateGoalAction::class);

    $goal = $action->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Facturación Mensual 2K',
        type: GoalType::MonthlyRevenue,
        monthlyTarget: '2000.00000000',
    ));

    expect($goal)->toBeInstanceOf(Goal::class)
        ->and($goal->type)->toBe(GoalType::MonthlyRevenue)
        ->and((float) $goal->monthly_target)->toBe(2000.0);
});

test('strictly blocks goal contributions when active debts exist', function () {
    // Create an active debt with current balance > 0
    Debt::query()->create([
        'workspace_id' => $this->workspace->id,
        'name' => 'Tarjeta de Crédito',
        'creditor' => 'Banco X',
        'currency_code' => 'USD',
        'initial_balance' => '1500.00',
        'current_balance' => '1200.00',
        'annual_interest_rate' => '24.00',
        'minimum_payment' => '100.00',
        'payment_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop nueva M4',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    $contributeAction = app(ContributeToGoalAction::class);

    expect(fn () => $contributeAction->execute(new ContributeToGoalDTO(
        workspaceId: $this->workspace->id,
        goalId: $goal->id,
        amount: '200.00',
        notes: 'Aporte con deuda activa',
    )))->toThrow(GoalContributionBlockedByDebtException::class);

    // Goal amount remains 0
    expect((float) $goal->fresh()->current_amount)->toBe(0.0);
});

test('permits goal contribution and recalculates completion date when debts are fully paid', function () {
    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop nueva M4',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00',
        plannedMonthlyQuota: '300.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    $contributeAction = app(ContributeToGoalAction::class);

    $contribution = $contributeAction->execute(new ContributeToGoalDTO(
        workspaceId: $this->workspace->id,
        goalId: $goal->id,
        amount: '500.00',
        notes: 'Primer ahorro sin deudas',
    ));

    expect($contribution->amount)->toBe('500.00000000');
    expect((float) $goal->fresh()->current_amount)->toBe(500.0);
});

test('marks goal as completed when contribution meets or exceeds target', function () {
    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Curso Online',
        type: GoalType::TargetAmount,
        targetAmount: '200.00',
        startDate: '2026-01-01',
        targetDate: '2026-03-01',
    ));

    $contributeAction = app(ContributeToGoalAction::class);

    $contributeAction->execute(new ContributeToGoalDTO(
        workspaceId: $this->workspace->id,
        goalId: $goal->id,
        amount: '250.00',
    ));

    $refreshed = $goal->fresh();
    expect($refreshed->status)->toBe(GoalStatus::Completed)
        ->and((float) $refreshed->current_amount)->toBe(250.0);
});

test('runs what-if scenario action returning dynamic projection metrics', function () {
    $createAction = app(CreateGoalAction::class);
    $goal = $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop 3000',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00',
        plannedMonthlyQuota: '300.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    $simulateAction = app(SimulateGoalScenarioAction::class);

    $result = $simulateAction->execute(new SimulateGoalDTO(
        goalId: $goal->id,
        simulatedMonthlyContribution: '500.00',
        oneOffContribution: '500.00',
    ));

    expect($result['is_accelerated'])->toBeTrue()
        ->and($result['is_delayed'])->toBeFalse()
        ->and((float) $result['simulated_current_amount'])->toBe(500.0)
        ->and((float) $result['remaining_amount'])->toBe(2500.0);
});

test('overview action aggregates sinking funds, revenue goals, and debt gating status', function () {
    $createAction = app(CreateGoalAction::class);

    $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Laptop',
        type: GoalType::TargetAmount,
        targetAmount: '3000.00',
        startDate: '2026-01-01',
        targetDate: '2026-11-01',
    ));

    $createAction->execute(new CreateGoalDTO(
        workspaceId: $this->workspace->id,
        name: 'Facturación $2000',
        type: GoalType::MonthlyRevenue,
        monthlyTarget: '2000.00',
    ));

    $overviewAction = app(GetGoalsOverviewAction::class);
    $overview = $overviewAction->execute($this->workspace->id);

    expect(count($overview->sinkingFundGoals))->toBe(1)
        ->and($overview->monthlyRevenueGoal)->not->toBeNull()
        ->and($overview->monthlyRevenueGoal['monthly_target'])->toBe('2000.00000000')
        ->and($overview->hasActiveUncoveredDebts)->toBeFalse()
        ->and($overview->activeGoalsCount)->toBe(1);
});
