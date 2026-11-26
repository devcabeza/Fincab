<?php

declare(strict_types=1);

use App\Application\Budget\Actions\SyncExchangeRatesAction;
use App\Livewire\Budget\AccountsAndRatesView;
use App\Livewire\Budget\BudgetSheetView;
use App\Livewire\Budget\DashboardOverview;
use App\Models\BudgetItem;
use App\Models\BudgetPeriod;
use App\Models\CurrencyRate;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('fetches and stores live exchange rates from external providers', function () {
    Http::fake([
        'https://ve.dolarapi.com/v1/dolares' => Http::response([
            ['fuente' => 'oficial', 'promedio' => 922.56],
            ['fuente' => 'paralelo', 'promedio' => 1050.00],
        ], 200),
        'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search' => Http::sequence()
            ->push(['data' => [['adv' => ['price' => '1040.50']]]], 200) // VES
            ->push(['data' => [['adv' => ['price' => '4150.00']]]], 200), // COP
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'rates' => ['COP' => 4200.00],
        ], 200),
    ]);

    /** @var SyncExchangeRatesAction $action */
    $action = app(SyncExchangeRatesAction::class);
    $count = $action->execute();

    expect($count)->toBeGreaterThan(0);

    $bcvQuote = CurrencyRate::getQuote('VES', 'USD', 'bcv');
    expect((float) $bcvQuote)->toBe(922.56);

    $paraleloQuote = CurrencyRate::getQuote('VES', 'USD', 'paralelo');
    expect((float) $paraleloQuote)->toBe(1050.00);

    $binanceVesQuote = CurrencyRate::getQuote('VES', 'USD', 'binance');
    expect((float) $binanceVesQuote)->toBe(1040.50);

    $copOfficialQuote = CurrencyRate::getQuote('COP', 'USD', 'official');
    expect((float) $copOfficialQuote)->toBe(4200.00);
});

it('executes rates:sync artisan command successfully', function () {
    Http::fake([
        'https://ve.dolarapi.com/v1/dolares' => Http::response([
            ['fuente' => 'oficial', 'promedio' => 920.00],
        ], 200),
        'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search' => Http::response([], 200),
        'https://open.er-api.com/v6/latest/USD' => Http::response(['rates' => ['COP' => 4100.00]], 200),
    ]);

    $this->artisan('rates:sync')->assertSuccessful();
    expect(CurrencyRate::query()->where('source', 'bcv')->exists())->toBeTrue();
});

it('syncs live exchange rates on-demand in AccountsAndRatesView', function () {
    Http::fake([
        'https://ve.dolarapi.com/v1/dolares' => Http::response([
            ['fuente' => 'oficial', 'promedio' => 930.00],
        ], 200),
        'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search' => Http::response([], 200),
        'https://open.er-api.com/v6/latest/USD' => Http::response(['rates' => ['COP' => 4100.00]], 200),
    ]);

    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    Livewire::test(AccountsAndRatesView::class)
        ->assertOk()
        ->call('syncLiveRates')
        ->assertSee('Tasas de cambio sincronizadas en vivo con éxito');

    $quote = CurrencyRate::getQuote('VES', 'USD', 'bcv');
    expect((float) $quote)->toBe(930.00);
});

it('renders the minimalist exchange rates banner in global application header', function () {
    CurrencyRate::query()->create([
        'from_currency' => 'VES',
        'to_currency' => 'USD',
        'source' => 'bcv',
        'quote_rate' => '925.50000000',
        'rate' => '0.001080497029',
        'effective_on' => now()->toDateString(),
    ]);

    CurrencyRate::query()->create([
        'from_currency' => 'VES',
        'to_currency' => 'USD',
        'source' => 'paralelo',
        'quote_rate' => '1110.00000000',
        'rate' => '0.000900900901',
        'effective_on' => now()->toDateString(),
    ]);

    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    $this->actingAs($user);

    Livewire::test(DashboardOverview::class)
        ->assertOk()
        ->assertSee('BCV')
        ->assertSee('925.50')
        ->assertSee('Paralelo')
        ->assertSee('1,110.00');
});

it('automatically recalculates USD budget items from VES local amount in BudgetSheetView', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    // Seed BCV rate: 1 USD = 100 VES
    CurrencyRate::query()->create([
        'from_currency' => 'VES',
        'to_currency' => 'USD',
        'source' => 'bcv',
        'quote_rate' => '100.00000000',
        'rate' => '0.010000000000',
        'effective_on' => now()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test(BudgetSheetView::class)
        ->assertOk()
        ->call('openCreateModal')
        ->set('currency', 'VES')
        ->set('rateSource', 'bcv')
        ->set('localAmount', '1000.00')
        ->assertSet('totalAmount', '10.00')
        ->assertSet('amountQ1', '5.00')
        ->assertSet('amountQ2', '5.00')
        ->set('title', 'Mercado en Bolívares')
        ->call('saveItem')
        ->assertSee('Mercado en Bolívares');

    $item = BudgetItem::query()->where('title', 'Mercado en Bolívares')->first();
    expect($item)->not->toBeNull()
        ->and((float) $item->amount_q1)->toBe(5.0)
        ->and((float) $item->amount_q2)->toBe(5.0);
});

it('automatically converts local amount to USD in DashboardOverview quick entry modal', function () {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create([
        'owner_id' => $user->id,
        'name' => 'Personal',
        'type' => 'personal',
        'base_currency' => 'USD',
    ]);

    BudgetPeriod::query()->create([
        'workspace_id' => $workspace->id,
        'period_start' => '2026-09-01',
        'base_currency' => 'USD',
    ]);

    // Seed BCV rate: 1 USD = 100 VES
    CurrencyRate::query()->create([
        'from_currency' => 'VES',
        'to_currency' => 'USD',
        'source' => 'bcv',
        'quote_rate' => '100.00000000',
        'rate' => '0.010000000000',
        'effective_on' => now()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test(DashboardOverview::class)
        ->assertOk()
        ->set('showQuickEntryModal', true)
        ->set('quickCurrency', 'VES')
        ->set('quickRateSource', 'bcv')
        ->set('quickLocalAmount', '500.00')
        ->assertSet('quickAmount', '5.00')
        ->set('quickTitle', 'Almuerzo Rápido')
        ->call('saveQuickEntry');

    $item = BudgetItem::query()->where('title', 'Almuerzo Rápido')->first();
    expect($item)->not->toBeNull()
        ->and((float) $item->amount_q1)->toBe(2.5)
        ->and((float) $item->amount_q2)->toBe(2.5);
});
