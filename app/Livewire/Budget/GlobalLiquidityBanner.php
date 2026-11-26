<?php

declare(strict_types=1);

namespace App\Livewire\Budget;

use App\Application\Budget\Actions\GetLiquidityMetricsAction;
use App\Application\Budget\DTOs\LiquidityMetricsDTO;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalLiquidityBanner extends Component
{
    public bool $showBreakdownModal = false;

    public ?int $workspaceId = null;

    public ?int $periodId = null;

    public function mount(?int $workspaceId = null, ?int $periodId = null): void
    {
        $this->workspaceId = $workspaceId;
        $this->periodId = $periodId;
    }

    #[On('budget-item-updated')]
    #[On('account-updated')]
    public function refreshMetrics(): void
    {
        // Re-renders with fresh metrics
    }

    public function toggleBreakdownModal(): void
    {
        $this->showBreakdownModal = ! $this->showBreakdownModal;
    }

    public function openBreakdownModal(): void
    {
        $this->showBreakdownModal = true;
    }

    public function closeBreakdownModal(): void
    {
        $this->showBreakdownModal = false;
    }

    public function render(GetLiquidityMetricsAction $action): View
    {
        $user = Auth::user();
        $targetWorkspaceId = $this->workspaceId;

        if ($targetWorkspaceId === null && $user instanceof User) {
            $workspace = Workspace::query()->where('owner_id', $user->id)->first();
            $targetWorkspaceId = $workspace?->id;
        }

        /** @var LiquidityMetricsDTO $metrics */
        $metrics = $targetWorkspaceId !== null
            ? $action->execute($targetWorkspaceId, $this->periodId)
            : new LiquidityMetricsDTO(
                salary: '0.00000000',
                sideProjects: '0.00000000',
                investments: '0.00000000',
                otherIncomes: '0.00000000',
                currentIncomesTotal: '0.00000000',
                previousPeriodSavings: '0.00000000',
                totalInflows: '0.00000000',
                totalExpenses: '0.00000000',
                paidExpenses: '0.00000000',
                pendingExpenses: '0.00000000',
                netLiquidity: '0.00000000',
            );

        return view('livewire.budget.global-liquidity-banner', [
            'metrics' => $metrics,
        ]);
    }
}
