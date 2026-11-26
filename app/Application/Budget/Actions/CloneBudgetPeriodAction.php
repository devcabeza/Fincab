<?php

declare(strict_types=1);

namespace App\Application\Budget\Actions;

use App\Application\Budget\DTOs\CloneBudgetPeriodDTO;
use App\Domain\Budget\Exceptions\BudgetPeriodAlreadyExistsException;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use Illuminate\Support\Facades\DB;

final class CloneBudgetPeriodAction
{
    public function execute(CloneBudgetPeriodDTO $dto): BudgetPeriod
    {
        return DB::transaction(function () use ($dto): BudgetPeriod {
            /** @var BudgetPeriod $source */
            $source = BudgetPeriod::query()->with(['buckets', 'items'])->findOrFail($dto->sourcePeriodId);

            $exists = BudgetPeriod::query()
                ->where('workspace_id', $source->workspace_id)
                ->where('period_start', $dto->targetPeriodStart)
                ->exists();

            if ($exists) {
                throw BudgetPeriodAlreadyExistsException::forPeriod($dto->targetPeriodStart);
            }

            /** @var BudgetPeriod $newPeriod */
            $newPeriod = BudgetPeriod::query()->create([
                'workspace_id' => $source->workspace_id,
                'period_start' => $dto->targetPeriodStart,
                'base_currency' => $dto->targetBaseCurrency ?? $source->base_currency,
                'debt_strategy' => $source->debt_strategy,
                'extra_debt_payment_percent' => $source->extra_debt_payment_percent,
            ]);

            $bucketMap = [];
            $discretionaryBucketIds = [];

            foreach ($source->buckets as $bucket) {
                $newBucket = BudgetBucket::query()->create([
                    'workspace_id' => $source->workspace_id,
                    'budget_period_id' => $newPeriod->id,
                    'key' => $bucket->key,
                    'name' => $bucket->name,
                    'allocation_percent' => $bucket->allocation_percent,
                    'priority' => $bucket->priority,
                ]);

                $bucketMap[$bucket->id] = $newBucket->id;

                if ($bucket->key === 'discretionary') {
                    $discretionaryBucketIds[] = $bucket->id;
                }
            }

            $recurringItems = $source->items->where('is_recurring', true);

            foreach ($recurringItems as $item) {
                $newBucketId = $item->budget_bucket_id !== null && isset($bucketMap[$item->budget_bucket_id])
                    ? $bucketMap[$item->budget_bucket_id]
                    : null;

                $isDiscretionary = $item->budget_bucket_id !== null && in_array($item->budget_bucket_id, $discretionaryBucketIds, true);

                $q1 = $isDiscretionary ? '0.00000000' : (string) $item->amount_q1;
                $q2 = $isDiscretionary ? '0.00000000' : (string) $item->amount_q2;

                BudgetItem::query()->create([
                    'workspace_id' => $source->workspace_id,
                    'budget_period_id' => $newPeriod->id,
                    'budget_bucket_id' => $newBucketId,
                    'debt_id' => $item->debt_id,
                    'account_id' => $item->account_id,
                    'title' => $item->title,
                    'item_type' => $item->item_type,
                    'income_category' => $item->income_category,
                    'split_mode' => $item->split_mode,
                    'amount_q1' => $q1,
                    'amount_q2' => $q2,
                    'status' => 'pending',
                    'is_recurring' => true,
                    'minimum_payment_protected' => $item->minimum_payment_protected,
                ]);
            }

            return $newPeriod->refresh();
        });
    }
}
