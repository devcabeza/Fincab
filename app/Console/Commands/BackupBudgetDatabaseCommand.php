<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\CurrencyRate;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\FinancialTransaction;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupBudgetDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budget:backup {--format=json : Backup export format} {--path= : Target export path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete JSON backup of financial workspaces and budget entities';

    public function handle(): int
    {
        $targetPath = $this->option('path');

        if ($targetPath === null || $targetPath === '') {
            $backupDir = storage_path('app/backups');
            $timestamp = now()->format('Y_m_d_His');
            $targetPath = "{$backupDir}/budget_backup_{$timestamp}.json";
        }

        $dir = dirname((string) $targetPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $data = [
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
            'workspaces' => Workspace::query()->get()->toArray(),
            'accounts' => Account::query()->get()->toArray(),
            'debts' => Debt::query()->get()->toArray(),
            'debt_payments' => DebtPayment::query()->get()->toArray(),
            'budget_periods' => BudgetPeriod::query()->get()->toArray(),
            'budget_buckets' => BudgetBucket::query()->get()->toArray(),
            'budget_items' => BudgetItem::query()->get()->toArray(),
            'financial_transactions' => FinancialTransaction::query()->get()->toArray(),
            'currency_rates' => CurrencyRate::query()->get()->toArray(),
        ];

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            $this->error('Error al serializar los datos financieros a formato JSON.');

            return self::FAILURE;
        }

        File::put((string) $targetPath, $encoded);

        $this->info('✓ Respaldo financiero generado exitosamente:');
        $this->line("  Ruta: {$targetPath}");
        $this->line('  Carteras respaldadas: '.count($data['workspaces']));
        $this->line('  Deudas respaldadas: '.count($data['debts']));
        $this->line('  Períodos presupuestarios: '.count($data['budget_periods']));
        $this->line('  Partidas presupuestarias: '.count($data['budget_items']));

        return self::SUCCESS;
    }
}
