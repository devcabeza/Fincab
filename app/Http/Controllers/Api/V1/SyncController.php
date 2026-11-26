<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Budget\Enums\SplitMode;
use App\Domain\Budget\Services\QuincenaCalculator;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\CurrencyRate;
use App\Models\Debt;
use App\Models\FinancialTransaction;
use App\Models\SyncMutation;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SyncController extends Controller
{
    public function __construct(
        private readonly QuincenaCalculator $quincenaCalculator = new QuincenaCalculator,
    ) {}

    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'integer'],
            'mutations' => ['present', 'array'],
            'mutations.*.id' => ['required', 'string'],
            'mutations.*.entity' => ['required', 'string'],
            'mutations.*.action' => ['required', 'string'],
            'mutations.*.payload' => ['required', 'array'],
        ]);

        /** @var Workspace $workspace */
        $workspace = Workspace::query()->findOrFail($validated['workspace_id']);

        if ($workspace->owner_id !== $request->user()?->id) {
            abort(403, 'No autorizado para acceder a este workspace.');
        }

        $applied = [];

        DB::transaction(function () use ($workspace, $validated, &$applied): void {
            foreach ($validated['mutations'] as $mutation) {
                $mutationId = (string) $mutation['id'];
                $entity = (string) $mutation['entity'];
                $action = (string) $mutation['action'];
                $payload = (array) $mutation['payload'];

                /** @var SyncMutation|null $existing */
                $existing = SyncMutation::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('mutation_id', $mutationId)
                    ->first();

                if ($existing !== null) {
                    $applied[] = [
                        'mutation_id' => $mutationId,
                        'entity_id' => $existing->entity_id,
                        'status' => 'already_applied',
                    ];

                    continue;
                }

                $entityId = null;

                if ($entity === 'budget_item') {
                    if ($action === 'create') {
                        $q1 = isset($payload['amount_q1']) && is_numeric($payload['amount_q1'])
                            ? (string) $payload['amount_q1']
                            : '0.00000000';
                        $q2 = isset($payload['amount_q2']) && is_numeric($payload['amount_q2'])
                            ? (string) $payload['amount_q2']
                            : '0.00000000';

                        /** @var BudgetItem $item */
                        $item = BudgetItem::query()->create([
                            'workspace_id' => $workspace->id,
                            'budget_period_id' => $payload['budget_period_id'],
                            'budget_bucket_id' => $payload['budget_bucket_id'] ?? null,
                            'debt_id' => $payload['debt_id'] ?? null,
                            'title' => (string) ($payload['title'] ?? 'Sin título'),
                            'item_type' => (string) ($payload['item_type'] ?? 'expense'),
                            'split_mode' => (string) ($payload['split_mode'] ?? 'equal'),
                            'amount_q1' => $q1,
                            'amount_q2' => $q2,
                            'status' => (string) ($payload['status'] ?? 'pending'),
                            'is_recurring' => (bool) ($payload['is_recurring'] ?? false),
                            'minimum_payment_protected' => (bool) ($payload['minimum_payment_protected'] ?? false),
                        ]);

                        $entityId = $item->id;
                    } elseif ($action === 'update' && isset($payload['id'])) {
                        /** @var BudgetItem $item */
                        $item = BudgetItem::query()
                            ->where('workspace_id', $workspace->id)
                            ->findOrFail($payload['id']);

                        $updates = [];
                        if (isset($payload['status'])) {
                            $updates['status'] = (string) $payload['status'];
                        }
                        if (isset($payload['title'])) {
                            $updates['title'] = (string) $payload['title'];
                        }
                        if (isset($payload['amount_q1'])) {
                            $updates['amount_q1'] = (string) $payload['amount_q1'];
                        }
                        if (isset($payload['amount_q2'])) {
                            $updates['amount_q2'] = (string) $payload['amount_q2'];
                        }

                        $item->update($updates);
                        $entityId = $item->id;
                    } elseif ($action === 'delete' && isset($payload['id'])) {
                        BudgetItem::query()
                            ->where('workspace_id', $workspace->id)
                            ->where('id', $payload['id'])
                            ->delete();
                        $entityId = (int) $payload['id'];
                    }
                }

                SyncMutation::query()->create([
                    'workspace_id' => $workspace->id,
                    'mutation_id' => $mutationId,
                    'entity_type' => $entity,
                    'entity_id' => $entityId,
                    'applied_at' => now(),
                ]);

                $applied[] = [
                    'mutation_id' => $mutationId,
                    'entity_id' => $entityId,
                    'status' => 'applied',
                ];
            }
        });

        return ApiResponse::success(
            data: [
                'applied' => $applied,
                'server_time' => now()->toIso8601String(),
            ],
            message: 'Sincronización completada exitosamente.',
        );
    }

    public function pull(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'integer'],
            'since' => ['nullable', 'string'],
        ]);

        /** @var Workspace $workspace */
        $workspace = Workspace::query()->findOrFail($validated['workspace_id']);

        if ($workspace->owner_id !== $request->user()?->id) {
            abort(403, 'No autorizado para acceder a este workspace.');
        }

        $sinceTimestamp = null;
        if (! empty($validated['since'])) {
            try {
                $rawSince = (string) $validated['since'];
                $sanitized = str_contains($rawSince, ' ') ? str_replace(' ', '+', $rawSince) : $rawSince;
                $sinceTimestamp = Carbon::parse($sanitized)->toDateTimeString();
            } catch (\Throwable) {
                $sinceTimestamp = null;
            }
        }

        $accounts = Account::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $periods = BudgetPeriod::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $buckets = BudgetBucket::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $items = BudgetItem::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $debts = Debt::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $transactions = FinancialTransaction::query()
            ->where('workspace_id', $workspace->id)
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        $rates = CurrencyRate::query()
            ->when($sinceTimestamp, fn ($query) => $query->where('updated_at', '>=', $sinceTimestamp))
            ->get();

        return ApiResponse::success(
            data: [
                'server_time' => now()->toIso8601String(),
                'workspace' => $workspace,
                'accounts' => $accounts,
                'budget_periods' => $periods,
                'budget_buckets' => $buckets,
                'budget_items' => $items,
                'debts' => $debts,
                'financial_transactions' => $transactions,
                'currency_rates' => $rates,
            ],
            message: 'Datos sincronizados exitosamente.',
        );
    }

    public function quickEntry(Request $request, int $workspaceId): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'budget_period_id' => ['required', 'integer'],
            'debt_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
        ]);

        /** @var Workspace $workspace */
        $workspace = Workspace::query()->findOrFail($workspaceId);

        if ($workspace->owner_id !== $request->user()?->id) {
            abort(403, 'No autorizado para acceder a este workspace.');
        }

        $amount = (string) $validated['amount'];
        $numericAmount = is_numeric($amount) ? $amount : '0.00000000';
        $split = $this->quincenaCalculator->calculateSplit($numericAmount, SplitMode::Equal);

        $debt = null;
        if (isset($validated['debt_id'])) {
            /** @var Debt $debt */
            $debt = Debt::query()
                ->where('workspace_id', $workspace->id)
                ->findOrFail($validated['debt_id']);
        }

        /** @var BudgetItem $item */
        $item = BudgetItem::query()->create([
            'workspace_id' => $workspace->id,
            'budget_period_id' => $validated['budget_period_id'],
            'debt_id' => $debt?->id,
            'title' => trim($validated['title']),
            'item_type' => 'expense',
            'split_mode' => 'equal',
            'amount_q1' => $split['q1'],
            'amount_q2' => $split['q2'],
            'status' => 'paid',
            'is_recurring' => false,
            'minimum_payment_protected' => false,
        ]);

        $remainingBalance = $debt !== null
            ? (string) $debt->fresh()->current_balance
            : null;

        return ApiResponse::success(
            data: [
                'budget_item_id' => $item->id,
                'remaining_balance' => $remainingBalance,
            ],
            message: 'Registro rápido guardado exitosamente.',
        );
    }
}
