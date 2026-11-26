<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\BudgetBucket;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\Debt;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access to sync endpoints', function () {
    $this->postJson('/api/v1/sync/push', [])
        ->assertUnauthorized();

    $this->getJson('/api/v1/sync/pull?workspace_id=1')
        ->assertUnauthorized();
});

it('rejects access to workspaces owned by other users', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $workspace = Workspace::query()->create([
        'owner_id' => $owner->id,
        'name' => 'Owner Workspace',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    Sanctum::actingAs($otherUser);

    $this->postJson('/api/v1/sync/push', [
        'workspace_id' => $workspace->id,
        'mutations' => [],
    ])->assertForbidden();

    $this->getJson("/api/v1/sync/pull?workspace_id={$workspace->id}")
        ->assertForbidden();
});

it('pushes offline recorded budget item mutations idempotently', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Cash',
        'currency_code' => 'USD',
        'opening_balance' => '500.00',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $bucket = BudgetBucket::query()->create([
        'workspace_id' => $workspace->id,
        'budget_period_id' => $period->id,
        'key' => 'essentials',
        'name' => 'Gastos Esenciales',
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Credit Card',
        'currency_code' => 'USD',
        'initial_balance' => '800.00',
        'current_balance' => '800.00',
        'minimum_payment' => '100.00',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $mutationId1 = 'mutation-create-item-001';
    $mutationId2 = 'mutation-pay-debt-002';

    $payload = [
        'workspace_id' => $workspace->id,
        'mutations' => [
            [
                'id' => $mutationId1,
                'entity' => 'budget_item',
                'action' => 'create',
                'payload' => [
                    'budget_period_id' => $period->id,
                    'budget_bucket_id' => $bucket->id,
                    'title' => 'Offline Groceries',
                    'item_type' => 'expense',
                    'split_mode' => 'equal',
                    'amount_q1' => '50.00',
                    'amount_q2' => '50.00',
                    'status' => 'pending',
                ],
            ],
            [
                'id' => $mutationId2,
                'entity' => 'budget_item',
                'action' => 'create',
                'payload' => [
                    'budget_period_id' => $period->id,
                    'debt_id' => $debt->id,
                    'title' => 'Credit Card Payment',
                    'item_type' => 'expense',
                    'split_mode' => 'equal',
                    'amount_q1' => '100.00',
                    'amount_q2' => '0.00',
                    'status' => 'paid',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/sync/push', $payload);
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.applied.0.mutation_id', $mutationId1)
        ->assertJsonPath('data.applied.1.mutation_id', $mutationId2);

    expect(BudgetItem::query()->where('title', 'Offline Groceries')->exists())->toBeTrue();

    // Debt balance should be amortized from 800 down to 700
    expect($debt->fresh()->current_balance)->toBe('700.00000000');

    // Retrying the push with the same mutation IDs must be idempotent (no duplicate creation or amortization)
    $retryResponse = $this->postJson('/api/v1/sync/push', $payload);
    $retryResponse->assertOk();

    expect(BudgetItem::query()->where('title', 'Offline Groceries')->count())->toBe(1);
    expect($debt->fresh()->current_balance)->toBe('700.00000000');
});

it('pulls changes since a timestamp for mobile offline hydration', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Bank',
        'currency_code' => 'USD',
        'opening_balance' => '1000.00',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Car Loan',
        'currency_code' => 'USD',
        'initial_balance' => '5000.00',
        'current_balance' => '5000.00',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    // Initial pull without 'since' returns full workspace data
    $response = $this->getJson("/api/v1/sync/pull?workspace_id={$workspace->id}");
    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'server_time',
                'workspace',
                'accounts',
                'budget_periods',
                'budget_buckets',
                'budget_items',
                'debts',
                'financial_transactions',
                'currency_rates',
            ],
        ]);

    $data = $response->json('data');
    expect(count($data['accounts']))->toBe(1)
        ->and(count($data['budget_periods']))->toBe(1)
        ->and(count($data['debts']))->toBe(1);

    // Subsequent pull with future 'since' returns empty lists
    $futureSince = urlencode(now()->addHour()->toIso8601String());
    $futureResponse = $this->getJson("/api/v1/sync/pull?workspace_id={$workspace->id}&since={$futureSince}");
    $futureResponse->assertOk();

    $futureData = $futureResponse->json('data');
    expect(count($futureData['accounts']))->toBe(0)
        ->and(count($futureData['debts']))->toBe(0);
});

it('provides quick entry API for rapid expense/payment recording', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $account = Account::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Cash Wallet',
        'currency_code' => 'USD',
        'opening_balance' => '200.00',
    ]);

    $period = BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    $debt = Debt::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Store Credit',
        'currency_code' => 'USD',
        'initial_balance' => '300.00',
        'current_balance' => '300.00',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/workspaces/{$workspace->id}/quick-entry", [
        'title' => 'Quick Debt Payment',
        'amount' => '50.00',
        'budget_period_id' => $period->id,
        'debt_id' => $debt->id,
        'account_id' => $account->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.remaining_balance', '250.00000000');

    expect($debt->fresh()->current_balance)->toBe('250.00000000');
});
