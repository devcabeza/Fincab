<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Budget\Enums\ItemStatus;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Workspace;
use Illuminate\Console\Command;

class CheckBudgetRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budget:check-reminders {--workspace= : Target workspace ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check pending budget obligations and emit threshold alerts';

    public function handle(): int
    {
        $workspaceId = $this->option('workspace');

        $workspacesQuery = Workspace::query();
        if ($workspaceId !== null && is_numeric($workspaceId)) {
            $workspacesQuery->where('id', (int) $workspaceId);
        }

        $workspaces = $workspacesQuery->get();

        if ($workspaces->isEmpty()) {
            $this->info('No se encontraron carteras para verificar.');

            return self::SUCCESS;
        }

        $this->info('=== Recordatorios de Presupuesto y Alertas ===');

        foreach ($workspaces as $workspace) {
            $this->line("Verificando cartera: {$workspace->name} (ID: {$workspace->id})");

            $period = BudgetPeriod::query()
                ->where('workspace_id', $workspace->id)
                ->orderByDesc('period_start')
                ->first();

            if ($period === null) {
                continue;
            }

            // 1. Quincena obligations check (debt & essentials)
            $pendingObligations = BudgetItem::query()
                ->where('workspace_id', $workspace->id)
                ->where('budget_period_id', $period->id)
                ->where('status', ItemStatus::Pending->value)
                ->whereHas('budgetBucket', function ($q) {
                    $q->whereIn('key', ['debts', 'essentials']);
                })
                ->get();

            if ($pendingObligations->isNotEmpty()) {
                $this->warn("📌 Compromisos pendientes detectados en [{$workspace->name}]:");
                foreach ($pendingObligations as $item) {
                    $this->line("  - [{$item->title}] Q1: \${$item->amount_q1} | Q2: \${$item->amount_q2}");
                }
            } else {
                $this->info("✓ No hay cuotas de deuda ni gastos esenciales pendientes en [{$workspace->name}].");
            }

            // 2. Threshold alerts (85% and 100%)
            $totalIncome = (float) BudgetItem::query()
                ->where('budget_period_id', $period->id)
                ->where('item_type', 'income')
                ->selectRaw('COALESCE(SUM(CAST(amount_q1 AS NUMERIC) + CAST(amount_q2 AS NUMERIC)), 0) as total')
                ->value('total');

            if ($totalIncome <= 0) {
                $totalIncome = (float) BudgetItem::query()
                    ->where('budget_period_id', $period->id)
                    ->selectRaw('COALESCE(SUM(CAST(amount_q1 AS NUMERIC) + CAST(amount_q2 AS NUMERIC)), 0) as total')
                    ->value('total');
            }

            $buckets = BudgetBucket::query()
                ->where('budget_period_id', $period->id)
                ->get();

            foreach ($buckets as $bucket) {
                $percent = (float) $bucket->allocation_percent;
                $allocated = $totalIncome > 0 && $percent > 0 ? ($totalIncome * ($percent / 100)) : 0.0;

                if ($allocated <= 0) {
                    continue;
                }

                $spent = (float) BudgetItem::query()
                    ->where('budget_bucket_id', $bucket->id)
                    ->where('status', ItemStatus::Paid->value)
                    ->selectRaw('COALESCE(SUM(CAST(amount_q1 AS NUMERIC) + CAST(amount_q2 AS NUMERIC)), 0) as total')
                    ->value('total');

                $ratio = $spent / $allocated;

                if ($ratio >= 1.0) {
                    $pct = number_format($ratio * 100, 1);
                    $this->error("🚨 ALERTA: El bloque [{$bucket->name}] ha superado el 100% de su presupuesto ({$pct}% gastado).");
                } elseif ($ratio >= 0.85) {
                    $pct = number_format($ratio * 100, 1);
                    $this->warn("⚠️ ADVERTENCIA: El bloque [{$bucket->name}] superó el 85% de su presupuesto ({$pct}% gastado).");
                }
            }
        }

        return self::SUCCESS;
    }
}
