<?php

declare(strict_types=1);

namespace App\Infrastructure\Mobile;

use App\Models\SyncMutation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LocalSyncManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function queueMutation(
        int $workspaceId,
        string $entityType,
        string $action,
        array $payload,
        ?string $clientMutationId = null,
    ): SyncMutation {
        $clientMutationId ??= (string) Str::uuid();

        /** @var SyncMutation $mutation */
        $mutation = SyncMutation::query()->create([
            'workspace_id' => $workspaceId,
            'mutation_id' => $clientMutationId,
            'entity_type' => $entityType,
            'entity_id' => isset($payload['id']) ? (int) $payload['id'] : null,
            'applied_at' => now(),
        ]);

        return $mutation;
    }

    /**
     * @return Collection<int, SyncMutation>
     */
    public function getPendingMutations(int $workspaceId): Collection
    {
        return SyncMutation::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{pushed: int, pulled: int, status: string}
     */
    public function syncWithServer(int $workspaceId, string $token, ?string $serverBaseUrl = null): array
    {
        $baseUrl = rtrim($serverBaseUrl ?? config('app.url', 'http://localhost:8000'), '/');
        $mutations = $this->getPendingMutations($workspaceId);
        $pushedCount = 0;
        $pulledCount = 0;

        if ($mutations->isNotEmpty()) {
            $mutationsPayload = $mutations->map(fn (SyncMutation $m) => [
                'id' => $m->mutation_id,
                'entity' => $m->entity_type,
                'action' => 'create',
                'payload' => [
                    'id' => $m->entity_id,
                ],
            ])->all();

            $response = Http::withToken($token)
                ->acceptJson()
                ->post("{$baseUrl}/api/v1/sync/push", [
                    'workspace_id' => $workspaceId,
                    'mutations' => $mutationsPayload,
                ]);

            if ($response->successful()) {
                $pushedCount = count($mutationsPayload);
            }
        }

        $pullResponse = Http::withToken($token)
            ->acceptJson()
            ->get("{$baseUrl}/api/v1/sync/pull?workspace_id={$workspaceId}");

        if ($pullResponse->successful()) {
            $pulledCount = 1;
        }

        return [
            'pushed' => $pushedCount,
            'pulled' => $pulledCount,
            'status' => 'synced',
        ];
    }
}
