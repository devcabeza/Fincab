<div class="min-h-screen-safe flex flex-col w-full max-w-full">
    {{-- Header --}}
    <header class="border-b border-zinc-800/80 bg-zinc-900/80 backdrop-blur-xl sticky top-0 z-30 pt-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-8 h-8 select-none">
                <span class="font-bold text-lg tracking-tight text-white select-none">Fincab Presupuesto</span>
            </div>

            <div class="flex items-center gap-3">
                @if ($workspaces->count() > 1)
                    <select
                        wire:change="switchWorkspace($event.target.value)"
                        class="bg-zinc-800 border border-zinc-700 text-white text-xs sm:text-sm rounded-xl px-3 py-2 outline-none focus:border-indigo-500 cursor-pointer"
                    >
                        @foreach ($workspaces as $ws)
                            <option value="{{ $ws->id }}" @selected($ws->id === $selectedWorkspaceId)>{{ $ws->name }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="text-xs sm:text-sm font-semibold text-zinc-300 bg-zinc-800/80 px-3 py-1.5 rounded-xl border border-zinc-700/60">
                        {{ $currentWorkspace?->name }}
                    </span>
                @endif

                @if ($periods->isNotEmpty())
                    <select
                        wire:change="switchPeriod($event.target.value)"
                        class="bg-zinc-800 border border-zinc-700 text-white text-xs sm:text-sm rounded-xl px-3 py-2 outline-none focus:border-indigo-500 cursor-pointer"
                    >
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}" @selected($p->id === $selectedPeriodId)>
                                {{ $p->period_start->translatedFormat('F Y') }}
                            </option>
                        @endforeach
                    </select>
                @endif

                {{-- User Avatar Dropdown --}}
                @if (Auth::user())
                    <x-dropdown align="end" width="w-64">
                        <x-slot:trigger>
                            <div class="flex items-center gap-3 p-1.5 rounded-2xl hover:bg-zinc-800/60 transition-colors cursor-pointer group">
                                <x-avatar :initials="Auth::user()->initials()" size="sm" />
                                <div class="text-left hidden sm:block">
                                    <div class="text-sm font-medium text-white truncate max-w-[140px]">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-zinc-400 truncate max-w-[140px]">{{ Auth::user()->email }}</div>
                                </div>
                            </div>
                        </x-slot:trigger>

                        <x-dropdown-header>
                            <div class="font-semibold text-white truncate">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-zinc-400 truncate font-normal">{{ Auth::user()->email }}</div>
                        </x-dropdown-header>

                        <x-dropdown-separator />

                        <x-dropdown-item href="{{ route('profile') }}">
                            Perfil
                        </x-dropdown-item>

                        <x-dropdown-item href="{{ route('settings') }}">
                            Configuraciones
                        </x-dropdown-item>

                        <x-dropdown-separator />

                        <x-dropdown-item :action="route('auth.logout')" variant="danger">
                            Cerrar sesión
                        </x-dropdown-item>
                    </x-dropdown>
                @endif
            </div>
        </div>

        {{-- Desktop Navigation Tabs & Live Rates Ticker --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between border-t border-zinc-800/60 text-sm gap-2 py-1 md:py-0">
            <div class="flex gap-4 overflow-x-auto no-scrollbar">
                <a href="{{ route('dashboard') }}" class="py-2.5 font-semibold text-indigo-400 border-b-2 border-indigo-500 whitespace-nowrap">Panel General</a>
                <a href="{{ route('budget.sheet') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Hoja Presupuestaria</a>
                <a href="{{ route('budget.debts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Centro de Deudas</a>
                <a href="{{ route('budget.accounts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Cuentas y Tasas</a>
            </div>

            <div class="pb-2 md:pb-0">
                <x-exchange-rates-banner />
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 space-y-6">
        {{-- Welcome User Greeting Banner --}}
        @if (Auth::user())
            <div class="flex items-center justify-between bg-zinc-900/60 border border-zinc-800 p-4 rounded-2xl">
                <div>
                    <h2 class="text-xl font-extrabold text-white">¡Hola, {{ Auth::user()->name }}!</h2>
                    <p class="text-xs text-zinc-400">Sesión activa con {{ Auth::user()->email }}</p>
                </div>
            </div>
        @endif

        {{-- Global Liquidity & Capital Banner --}}
        <livewire:budget.global-liquidity-banner :workspace-id="$selectedWorkspaceId" :period-id="$selectedPeriodId" wire:key="dashboard-global-liquidity-banner" />

        {{-- Leisure Block Warning Banner (if triggered) --}}
        @if ($cashFlowSummary && $cashFlowSummary->isLeisureBlocked)
            <x-alert variant="warning" class="border border-amber-500/30">
                <div class="font-semibold text-amber-300">¡Alerta de Bloqueo de Ocio activa!</div>
                <div class="text-xs sm:text-sm text-zinc-300">Ha superado el día 15 del mes y existen cuotas de deuda de la Primera Quincena en estado pendiente. La creación de nuevos gastos de ocio está restringida.</div>
            </x-alert>
        @endif

        {{-- Debt Thermometer Console --}}
        <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-zinc-900 border border-zinc-800 p-5 sm:p-8 shadow-2xl space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-indigo-400 text-xs font-bold uppercase tracking-wider">
                        <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                        </svg>
                        <span>Termómetro de Deuda Total</span>
                    </div>
                    <div class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight font-mono tabular-nums">
                        ${{ number_format((float) $totalCurrentDebt, 2) }}
                    </div>
                    <div class="text-xs sm:text-sm text-zinc-400 font-mono tabular-nums">
                        Deuda inicial: ${{ number_format((float) $totalInitialDebt, 2) }} · Amortizado: <span class="text-emerald-400 font-semibold">{{ $debtProgressPercent }}%</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <x-button wire:click="recalculateWaterfall" variant="neutral" class="bg-zinc-800 hover:bg-zinc-700 text-white min-h-[44px]">
                        <span>Recalcular Cascada</span>
                    </x-button>

                    <x-button wire:click="$toggle('showQuickEntryModal')" variant="primary" class="min-h-[44px]">
                        <span>+ Registro Rápido</span>
                    </x-button>

                    <x-button wire:click="$toggle('showCloneModal')" variant="neutral" class="bg-zinc-800 hover:bg-zinc-700 text-white min-h-[44px]">
                        <span>Clonar Mes</span>
                    </x-button>
                </div>
            </div>

            {{-- Progress bar --}}
            <div class="w-full bg-zinc-800/80 rounded-full h-3 overflow-hidden">
                <div
                    class="bg-indigo-500 h-3 rounded-full transition-all duration-500"
                    style="width: {{ min(100, $debtProgressPercent) }}%"
                ></div>
            </div>
        </div>

        {{-- Top KPIs Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <x-card>
                <div class="text-xs sm:text-sm text-zinc-400">Ingresos del Mes</div>
                <div class="text-xl sm:text-2xl font-bold text-white mt-1 font-mono tabular-nums">
                    ${{ $waterfallResult ? number_format((float) $waterfallResult->totalIncome, 2) : '0.00' }}
                </div>
            </x-card>

            <x-card>
                <div class="text-xs sm:text-sm text-zinc-400">Comprometido Q1 vs Q2</div>
                <div class="text-xs sm:text-sm font-semibold text-zinc-200 mt-1 font-mono tabular-nums">
                    Q1: ${{ $cashFlowSummary ? number_format((float) $cashFlowSummary->liquidNeededQ1, 2) : '0.00' }}
                </div>
                <div class="text-xs sm:text-sm font-semibold text-zinc-400 font-mono tabular-nums">
                    Q2: ${{ $cashFlowSummary ? number_format((float) $cashFlowSummary->liquidNeededQ2, 2) : '0.00' }}
                </div>
            </x-card>

            <x-card>
                <div class="text-xs sm:text-sm text-zinc-400">Saldo Líquido en Cuentas</div>
                <div class="text-xl sm:text-2xl font-bold text-emerald-400 mt-1 font-mono tabular-nums">
                    ${{ number_format((float) $totalLiquidCash, 2) }}
                </div>
            </x-card>

            <x-card>
                <div class="text-xs sm:text-sm text-zinc-400">Ahorro Real Proyectado</div>
                <div class="text-xl sm:text-2xl font-bold text-white mt-1 font-mono tabular-nums">
                    ${{ $cashFlowSummary ? number_format((float) $cashFlowSummary->realSavings, 2) : '0.00' }}
                </div>
            </x-card>
        </div>

        {{-- Budget Block Progress Bars (Cascada de Prioridad) --}}
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-bold text-white">Asignación Presupuestaria por Bloques (Cascada de Prioridad)</h3>
                <p class="text-xs text-zinc-400">Flujo ordenado por regla estricta Pay Debt First en 4 niveles operativos.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($bucketsWithProgress as $b)
                    <x-card>
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-bold text-white">{{ $b['name'] }}</span>
                            <span class="text-xs text-zinc-400 font-mono tabular-nums">
                                ${{ number_format((float) $b['spent'], 2) }} / ${{ number_format((float) $b['limit'], 2) }}
                            </span>
                        </div>

                        <div class="w-full bg-zinc-800 rounded-full h-2.5 mt-3 overflow-hidden">
                            @php
                                $colorClass = match (true) {
                                    $b['percent'] >= 100 => 'bg-rose-500',
                                    $b['percent'] >= 85 => 'bg-amber-500',
                                    default => 'bg-emerald-500',
                                };
                            @endphp
                            <div class="{{ $colorClass }} h-2.5 rounded-full transition-all duration-300" style="width: {{ $b['percent'] }}%"></div>
                        </div>

                        <div class="text-right text-[11px] text-zinc-400 mt-1.5 font-mono tabular-nums">
                            {{ number_format($b['percent'], 1) }}% utilizado
                        </div>
                    </x-card>
                @endforeach
            </div>
        </div>

        {{-- Active Debts Summary List --}}
        <div class="space-y-3">
            <div>
                <h3 class="text-lg font-bold text-white">Pasivos y Deudas Activas</h3>
                <p class="text-xs text-zinc-400">Balance vivo de tarjetas de crédito y préstamos bajo amortización.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @forelse ($debts as $debt)
                    <x-card>
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-bold text-white">{{ $debt->name }}</div>
                                <div class="text-xs text-zinc-400 font-mono tabular-nums">Tasa: {{ number_format((float) $debt->annual_interest_rate, 1) }}% anual</div>
                            </div>
                            <x-badge variant="neutral" size="sm" class="bg-zinc-800 text-zinc-300">
                                {{ $debt->currency_code }}
                            </x-badge>
                        </div>

                        <div class="mt-3 flex justify-between items-baseline">
                            <span class="text-xs text-zinc-400">Saldo vivo:</span>
                            <span class="text-base font-extrabold text-white font-mono tabular-nums">${{ number_format((float) $debt->current_balance, 2) }}</span>
                        </div>
                    </x-card>
                @empty
                    <div class="col-span-3 text-center py-8 text-zinc-500 text-sm bg-zinc-900/40 rounded-2xl border border-zinc-800">
                        No hay deudas registradas en este workspace.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Entry Modal --}}
    @if ($showQuickEntryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white">Registro Rápido de Gasto / Pago</h3>
                    <button
                        wire:click="$toggle('showQuickEntryModal')"
                        class="text-zinc-400 hover:text-white p-1 rounded-lg transition-colors"
                        aria-label="Cerrar modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveQuickEntry" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Concepto</label>
                        <x-input wire:model="quickTitle" placeholder="Ej: Pago de cuota o supermercado" />
                        @error('quickTitle') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Currency Selector & Dynamic Rate --}}
                    <div class="p-3 bg-zinc-950/70 border border-zinc-800 rounded-xl space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider">Moneda de Pago</span>
                            <div class="flex items-center gap-1 bg-zinc-900 p-0.5 rounded-lg border border-zinc-800">
                                @foreach (['USD' => '$ USD', 'VES' => 'Bs VES', 'COP' => '$ COP'] as $code => $label)
                                    @if ($quickCurrency === $code)
                                        <button
                                            type="button"
                                            wire:click="$set('quickCurrency', '{{ $code }}')"
                                            class="px-2.5 py-1 rounded text-xs font-semibold bg-indigo-600 text-white shadow-sm"
                                        >
                                            {{ $label }}
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="$set('quickCurrency', '{{ $code }}')"
                                            class="px-2.5 py-1 rounded text-xs font-semibold text-zinc-400 hover:text-white transition-colors"
                                        >
                                            {{ $label }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        @if ($quickCurrency !== 'USD')
                            <div class="pt-2 border-t border-zinc-800/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium text-zinc-400">Tasa de Referencia</span>
                                    <span class="text-[11px] font-mono tabular-nums text-indigo-400">
                                        1 USD = {{ $this->getQuickExchangeRate() ?? '---' }} {{ $quickCurrency }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                    @if ($quickCurrency === 'VES')
                                        <button
                                            type="button"
                                            wire:click="selectQuickRateSource('bcv')"
                                            class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'bcv' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                        >
                                            <div class="text-[10px] {{ $quickRateSource === 'bcv' ? 'text-indigo-200' : 'text-zinc-400' }}">BCV Oficial</div>
                                            <div class="font-mono tabular-nums text-[11px] truncate">{{ $latestBcv ? number_format((float) $latestBcv, 2) : 'N/D' }}</div>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="selectQuickRateSource('paralelo')"
                                            class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'paralelo' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                        >
                                            <div class="text-[10px] {{ $quickRateSource === 'paralelo' ? 'text-indigo-200' : 'text-zinc-400' }}">Paralelo</div>
                                            <div class="font-mono tabular-nums text-[11px] truncate">{{ $latestParalelo ? number_format((float) $latestParalelo, 2) : 'N/D' }}</div>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="selectQuickRateSource('binance')"
                                            class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'binance' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                        >
                                            <div class="text-[10px] {{ $quickRateSource === 'binance' ? 'text-indigo-200' : 'text-zinc-400' }}">Binance P2P</div>
                                            <div class="font-mono tabular-nums text-[11px] truncate">{{ $latestBinanceVes ? number_format((float) $latestBinanceVes, 2) : 'N/D' }}</div>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="selectQuickRateSource('bcv')"
                                            class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'bcv' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                        >
                                            <div class="text-[10px] {{ $quickRateSource === 'bcv' ? 'text-indigo-200' : 'text-zinc-400' }}">TRM Oficial</div>
                                            <div class="font-mono tabular-nums text-[11px] truncate">{{ $latestCop ? number_format((float) $latestCop, 0) : 'N/D' }}</div>
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="selectQuickRateSource('binance')"
                                            class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'binance' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                        >
                                            <div class="text-[10px] {{ $quickRateSource === 'binance' ? 'text-indigo-200' : 'text-zinc-400' }}">Binance P2P</div>
                                            <div class="font-mono tabular-nums text-[11px] truncate">P2P COP</div>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('manual')"
                                        class="px-2 py-1.5 rounded-lg text-left border transition-all text-xs {{ $quickRateSource === 'manual' ? 'bg-indigo-950/80 border-indigo-500 text-white font-bold' : 'bg-zinc-900 border-zinc-800 text-zinc-400 hover:text-white' }}"
                                    >
                                        <div class="text-[10px] {{ $quickRateSource === 'manual' ? 'text-indigo-200' : 'text-zinc-400' }}">Manual</div>
                                        <div class="text-[11px]">Personalizada</div>
                                    </button>
                                </div>

                                @if ($quickRateSource === 'manual')
                                    <div class="pt-1">
                                        <label class="block text-[11px] text-zinc-400 mb-1">Cotización Manual (1 USD = ? {{ $quickCurrency }})</label>
                                        <x-input wire:model.live="quickManualQuote" type="number" step="0.01" placeholder="Ej: 920.00" />
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-[11px] text-zinc-400 mb-1">Monto en {{ $quickCurrency }}</label>
                                    <x-input wire:model.live="quickLocalAmount" type="number" step="0.01" placeholder="Ej: 500.00" />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-zinc-300">
                                Monto Final a Presupuestar ($ USD)
                            </label>
                            @if ($quickCurrency !== 'USD')
                                <span class="text-[10px] text-indigo-400 font-medium">Calculado automáticamente</span>
                            @endif
                        </div>
                        <x-input wire:model="quickAmount" type="number" step="0.01" placeholder="0.00" />
                        @error('quickAmount') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuenta de Pago (Opcional)</label>
                        <select wire:model.live="quickAccountId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                            <option value="">Sin cuenta vinculada</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->currency_code }}) · Saldo: {{ $account->currency_code === 'USD' ? '$' . number_format((float) $account->opening_balance, 2) : number_format((float) $account->opening_balance, 2) . ' ' . $account->currency_code }}</option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        $quickSelectedAcc = $accounts->firstWhere('id', $quickAccountId);
                    @endphp
                    @if ($quickSelectedAcc && $quickSelectedAcc->currency_code !== 'USD')
                        <div class="p-3.5 bg-zinc-950/90 border border-indigo-500/40 rounded-xl space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-indigo-300 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Tasa de cambio para {{ $quickSelectedAcc->name }} ({{ $quickSelectedAcc->currency_code }})</span>
                                </span>
                                <span class="text-[11px] text-zinc-400 font-mono">
                                    1 USD = {{ $this->getQuickExchangeRate() ? number_format((float) $this->getQuickExchangeRate(), 2) : '1.00' }} {{ $quickSelectedAcc->currency_code }}
                                </span>
                            </div>

                            @if ($quickSelectedAcc->currency_code === 'VES')
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-xs">
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('bcv')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'bcv' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        BCV: {{ $latestBcv ? number_format((float) $latestBcv, 2) : '924.35' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('paralelo')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'paralelo' ? 'border-amber-500 bg-amber-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Paralelo: {{ $latestParalelo ? number_format((float) $latestParalelo, 2) : '1095.44' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('binance')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Binance P2P: {{ $latestBinanceVes ? number_format((float) $latestBinanceVes, 2) : '1090.00' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('manual')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Personalizada
                                    </button>
                                </div>
                            @elseif ($quickSelectedAcc->currency_code === 'COP')
                                <div class="grid grid-cols-3 gap-1.5 text-xs">
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('official')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'official' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        TRM: {{ $latestCop ? number_format((float) $latestCop, 0) : '3604' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('binance')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Binance: {{ $latestCopBinance ? number_format((float) $latestCopBinance, 0) : '3468' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectQuickRateSource('manual')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $quickRateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Personalizada
                                    </button>
                                </div>
                            @endif

                            @if ($quickRateSource === 'manual')
                                <div class="pt-1">
                                    <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Ingresa tu tasa personalizada (1 USD = ? {{ $quickSelectedAcc->currency_code }}):</label>
                                    <x-input wire:model.live.debounce.250ms="quickManualQuote" type="number" step="0.01" placeholder="Ej: 950.00" class="text-xs font-mono" />
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-xs bg-zinc-900/80 p-2.5 rounded-xl border border-zinc-800 font-mono">
                                <span class="text-zinc-400">Débito estimado en cuenta:</span>
                                <span class="font-bold text-emerald-400">
                                    @php
                                        $qRateVal = $this->getQuickExchangeRate();
                                    @endphp
                                    @if ($quickAmount && is_numeric($quickAmount) && (float) $quickAmount > 0 && $qRateVal && (float) $qRateVal > 0)
                                        {{ number_format((float) bcmul((string) $quickAmount, (string) $qRateVal, 2), 2) }} {{ $quickSelectedAcc->currency_code }}
                                    @else
                                        0.00 {{ $quickSelectedAcc->currency_code }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Vincular a Deuda (Opcional)</label>
                        <select wire:model="quickDebtId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="">Ninguna</option>
                            @foreach ($debts as $debt)
                                <option value="{{ $debt->id }}">{{ $debt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" wire:model="quickIsRecurring" id="quickIsRecurring" class="rounded bg-zinc-800 border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <label for="quickIsRecurring" class="text-xs text-zinc-300 cursor-pointer">Marcar como gasto recurrente (se clonará cada mes)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button wire:click="$toggle('showQuickEntryModal')" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Guardar
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Clone Month Modal --}}
    @if ($showCloneModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white">Apertura y Clonación de Mes</h3>
                    <button
                        wire:click="$toggle('showCloneModal')"
                        class="text-zinc-400 hover:text-white p-1 rounded-lg transition-colors"
                        aria-label="Cerrar modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-zinc-400 leading-relaxed">
                    Copia únicamente las partidas recurrentes, reinicia estados a Pendiente y limpia montos de ocio/variables a $0.00.
                </p>

                <form wire:submit="cloneMonth" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Fecha de inicio del nuevo mes</label>
                        <x-input wire:model="cloneTargetPeriod" type="date" />
                        @error('cloneTargetPeriod') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <x-button wire:click="$toggle('showCloneModal')" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Clonar Período
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bottom Navigation --}}
    <x-app-bottom-nav />
</div>
