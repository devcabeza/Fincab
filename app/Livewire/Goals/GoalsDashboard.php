<?php

declare(strict_types=1);

namespace App\Livewire\Goals;

use App\Application\Goal\Actions\ContributeToGoalAction;
use App\Application\Goal\Actions\CreateGoalAction;
use App\Application\Goal\Actions\GetGoalsOverviewAction;
use App\Application\Goal\Actions\SimulateGoalScenarioAction;
use App\Application\Goal\DTOs\ContributeToGoalDTO;
use App\Application\Goal\DTOs\CreateGoalDTO;
use App\Application\Goal\DTOs\SimulateGoalDTO;
use App\Domain\Goal\Enums\GoalType;
use App\Domain\Goal\Exceptions\GoalContributionBlockedByDebtException;
use App\Domain\Goal\Exceptions\InvalidGoalTargetException;
use App\Models\Goal;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sistema de Objetivos y Metas | Fincab')]
class GoalsDashboard extends Component
{
    public ?int $selectedWorkspaceId = null;

    public bool $showCreateModal = false;

    public bool $showContributeModal = false;

    public ?int $selectedGoalId = null;

    // Create Goal form state
    public string $name = '';

    public string $type = 'target_amount';

    public string $targetAmount = '';

    public string $monthlyTarget = '';

    public string $plannedMonthlyQuota = '';

    public string $startDate = '';

    public string $targetDate = '';

    public string $currencyCode = 'USD';

    public string $notes = '';

    // Contribute form state
    public string $contributionAmount = '';

    public string $contributionNotes = '';

    // What-If Simulator state
    public ?int $simulatingGoalId = null;

    public string $simulatedMonthly = '100.00';

    public string $simulatedOneOff = '0.00';

    /** @var array<string, mixed>|null */
    public ?array $simulationResult = null;

    public ?string $feedbackMessage = null;

    public ?string $feedbackType = null; // 'success' | 'error'

    public function mount(GetGoalsOverviewAction $overviewAction): void
    {
        $user = Auth::user();
        if ($user) {
            $workspace = $user->workspaces()->first();
            if (! $workspace) {
                $workspace = Workspace::query()->create([
                    'owner_id' => $user->id,
                    'name' => 'Finanzas Personales',
                    'type' => 'personal',
                    'base_currency' => 'USD',
                ]);
            }

            $this->selectedWorkspaceId = $workspace->id;
        }

        $this->startDate = now()->format('Y-m-d');
        $this->targetDate = now()->addMonths(10)->format('Y-m-d');

        $this->initializeDefaultSimulation($overviewAction);
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'name',
            'targetAmount',
            'monthlyTarget',
            'plannedMonthlyQuota',
            'notes',
            'feedbackMessage',
            'feedbackType',
        ]);
        $this->type = 'target_amount';
        $this->currencyCode = 'USD';
        $this->startDate = now()->format('Y-m-d');
        $this->targetDate = now()->addMonths(10)->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function createGoal(CreateGoalAction $action, GetGoalsOverviewAction $overviewAction): void
    {
        $this->feedbackMessage = null;

        $rules = [
            'name' => 'required|string|min:2|max:120',
            'type' => 'required|in:target_amount,monthly_revenue',
            'currencyCode' => 'required|string|size:3',
        ];

        if ($this->type === 'target_amount') {
            $rules['targetAmount'] = 'required|numeric|min:0.01';
            $rules['targetDate'] = 'required|date|after:startDate';
            $rules['plannedMonthlyQuota'] = 'nullable|numeric|min:0';
        } else {
            $rules['monthlyTarget'] = 'required|numeric|min:0.01';
        }

        $this->validate($rules, [
            'name.required' => 'El nombre de la meta es obligatorio.',
            'targetAmount.required' => 'El monto objetivo es requerido.',
            'targetAmount.min' => 'El monto debe ser mayor a 0.',
            'targetDate.required' => 'La fecha límite es obligatoria.',
            'targetDate.after' => 'La fecha límite debe ser posterior a hoy.',
            'monthlyTarget.required' => 'El objetivo de facturación mensual es requerido.',
        ]);

        try {
            $goalType = GoalType::from($this->type);

            $action->execute(new CreateGoalDTO(
                workspaceId: (int) $this->selectedWorkspaceId,
                name: $this->name,
                type: $goalType,
                targetAmount: $this->type === 'target_amount' ? (string) $this->targetAmount : null,
                monthlyTarget: $this->type === 'monthly_revenue' ? (string) $this->monthlyTarget : null,
                plannedMonthlyQuota: ! empty($this->plannedMonthlyQuota) ? (string) $this->plannedMonthlyQuota : null,
                startDate: $this->startDate,
                targetDate: $this->type === 'target_amount' ? $this->targetDate : null,
                currencyCode: $this->currencyCode,
                notes: $this->notes,
            ));

            $this->showCreateModal = false;
            $this->feedbackMessage = 'Meta creada con éxito y añadida a tu plan financiero.';
            $this->feedbackType = 'success';

            $this->initializeDefaultSimulation($overviewAction);
        } catch (InvalidGoalTargetException $e) {
            $this->addError('targetAmount', $e->getMessage());
        }
    }

    public function openContributeModal(int $goalId): void
    {
        $this->selectedGoalId = $goalId;
        $this->contributionAmount = '';
        $this->contributionNotes = '';
        $this->feedbackMessage = null;
        $this->showContributeModal = true;
    }

    public function contribute(ContributeToGoalAction $action, GetGoalsOverviewAction $overviewAction): void
    {
        $this->validate([
            'contributionAmount' => 'required|numeric|min:0.01',
        ], [
            'contributionAmount.required' => 'El monto a apartar es requerido.',
            'contributionAmount.min' => 'El monto debe ser mayor a cero.',
        ]);

        try {
            $action->execute(new ContributeToGoalDTO(
                workspaceId: (int) $this->selectedWorkspaceId,
                goalId: (int) $this->selectedGoalId,
                amount: (string) $this->contributionAmount,
                notes: $this->contributionNotes,
            ));

            $this->showContributeModal = false;
            $this->feedbackMessage = 'Aporte registrado correctamente en tu sobre virtual.';
            $this->feedbackType = 'success';

            $this->initializeDefaultSimulation($overviewAction);
        } catch (GoalContributionBlockedByDebtException $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function selectGoalForSimulation(int $goalId, SimulateGoalScenarioAction $simulateAction): void
    {
        $this->simulatingGoalId = $goalId;
        $goal = Goal::query()->find($goalId);
        if ($goal && bccomp((string) $goal->planned_monthly_quota, '0', 8) > 0) {
            $this->simulatedMonthly = (string) round((float) $goal->planned_monthly_quota, 2);
        } else {
            $this->simulatedMonthly = '100.00';
        }
        $this->simulatedOneOff = '0.00';
        $this->runSimulation($simulateAction);
    }

    public function updatedSimulatedMonthly(): void
    {
        $this->runSimulation(app(SimulateGoalScenarioAction::class));
    }

    public function updatedSimulatedOneOff(): void
    {
        $this->runSimulation(app(SimulateGoalScenarioAction::class));
    }

    public function runSimulation(SimulateGoalScenarioAction $simulateAction): void
    {
        if (! $this->simulatingGoalId) {
            $this->simulationResult = null;

            return;
        }

        $monthly = is_numeric($this->simulatedMonthly) && (float) $this->simulatedMonthly > 0
            ? (string) $this->simulatedMonthly
            : '1.00';

        $oneOff = is_numeric($this->simulatedOneOff) && (float) $this->simulatedOneOff >= 0
            ? (string) $this->simulatedOneOff
            : '0.00';

        try {
            $this->simulationResult = $simulateAction->execute(new SimulateGoalDTO(
                goalId: $this->simulatingGoalId,
                simulatedMonthlyContribution: $monthly,
                oneOffContribution: $oneOff,
            ));
        } catch (\Throwable) {
            $this->simulationResult = null;
        }
    }

    private function initializeDefaultSimulation(GetGoalsOverviewAction $overviewAction): void
    {
        if ($this->selectedWorkspaceId) {
            $overview = $overviewAction->execute($this->selectedWorkspaceId);
            if (! empty($overview->sinkingFundGoals)) {
                $firstGoal = $overview->sinkingFundGoals[0];
                $this->simulatingGoalId = (int) $firstGoal['id'];
                $this->simulatedMonthly = (string) round((float) $firstGoal['planned_monthly_quota'], 2);
                $this->simulatedOneOff = '0.00';
                $this->runSimulation(app(SimulateGoalScenarioAction::class));
            }
        }
    }

    public function render(GetGoalsOverviewAction $overviewAction): View
    {
        $overview = $overviewAction->execute((int) $this->selectedWorkspaceId);

        return view('livewire.goals.goals-dashboard', [
            'overview' => $overview,
        ]);
    }
}
