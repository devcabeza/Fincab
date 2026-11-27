<div class="min-h-screen-safe flex flex-col w-full max-w-full">
    {{-- Header --}}
    <header class="border-b border-zinc-800/80 bg-zinc-900/80 backdrop-blur-xl sticky top-0 z-30 pt-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-8 h-8 select-none">
                <span class="font-bold text-lg tracking-tight text-white select-none">Centro de Control de Deudas</span>
            </div>

            <x-button wire:click="openDebtModal" variant="primary" class="min-h-[44px]">
                <span>+ Nuevo Pasivo</span>
            </x-button>
        </div>

        {{-- Desktop Navigation Tabs & Live Rates Ticker --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between border-t border-zinc-800/60 text-sm gap-2 py-1 md:py-0">
            <div class="flex gap-4 overflow-x-auto no-scrollbar">
                <a href="{{ route('dashboard') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Panel General</a>
                <a href="{{ route('budget.sheet') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Hoja Presupuestaria</a>
                <a href="{{ route('budget.debts') }}" class="py-2.5 font-semibold text-indigo-400 border-b-2 border-indigo-500 whitespace-nowrap">Centro de Deudas</a>
                <a href="{{ route('budget.accounts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Cuentas y Tasas</a>
                <a href="{{ route('goals.index') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Metas y Objetivos</a>
            </div>

            <div class="pb-2 md:pb-0">
                <x-exchange-rates-banner />
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 space-y-6">
        {{-- Global Liquidity & Capital Banner --}}
        <livewire:budget.global-liquidity-banner :workspace-id="$selectedWorkspaceId" :period-id="$selectedPeriodId" wire:key="debts-global-liquidity-banner" />

        {{-- Strategy Selector & Simulator Bar --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Strategy Selector Card --}}
            <x-card>
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Estrategia Activa de Priorización</h3>
                    <p class="text-xs text-zinc-400">
                        Define a qué deuda se dirige el 100% de los abonos extraordinarios y aceleradores de flujo.
                    </p>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button
                            wire:click="switchStrategy('avalanche')"
                            class="p-3.5 rounded-xl border text-left transition-all min-h-[44px] {{ $activeStrategy === 'avalanche' ? 'bg-indigo-950/70 border-indigo-500 text-white shadow-sm' : 'bg-zinc-800/40 border-zinc-700/80 text-zinc-300 hover:bg-zinc-800 hover:text-white' }}"
                        >
                            <div class="flex items-center gap-1.5 font-bold text-xs sm:text-sm {{ $activeStrategy === 'avalanche' ? 'text-indigo-300' : 'text-zinc-200' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                </svg>
                                <span>Avalancha</span>
                            </div>
                            <div class="text-[11px] {{ $activeStrategy === 'avalanche' ? 'text-indigo-200/80' : 'text-zinc-400' }} mt-1 leading-relaxed">Mayor tasa de interés primero (Ahorro matemático óptimo)</div>
                        </button>

                        <button
                            wire:click="switchStrategy('snowball')"
                            class="p-3.5 rounded-xl border text-left transition-all min-h-[44px] {{ $activeStrategy === 'snowball' ? 'bg-indigo-950/70 border-indigo-500 text-white shadow-sm' : 'bg-zinc-800/40 border-zinc-700/80 text-zinc-300 hover:bg-zinc-800 hover:text-white' }}"
                        >
                            <div class="flex items-center gap-1.5 font-bold text-xs sm:text-sm {{ $activeStrategy === 'snowball' ? 'text-indigo-300' : 'text-zinc-200' }}">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Bola de Nieve</span>
                            </div>
                            <div class="text-[11px] {{ $activeStrategy === 'snowball' ? 'text-indigo-200/80' : 'text-zinc-400' }} mt-1 leading-relaxed">Menor saldo vivo primero (Victoria psicológica rápida)</div>
                        </button>
                    </div>
                </div>
            </x-card>

            {{-- Impact Simulator Card --}}
            <x-card>
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Simulador de Abonos Extraordinarios</h3>
                    <p class="text-xs text-zinc-400">
                        ¿Qué pasa si este mes le inyectas dinero adicional a tu deuda prioritaria?
                    </p>

                    @if ($sortedDebts->isEmpty())
                        <div class="bg-zinc-800/40 border border-zinc-800 rounded-xl p-3 text-xs text-zinc-400 leading-relaxed">
                            Registra tu primera deuda con <strong class="text-indigo-300">+ Nuevo Pasivo</strong> para proyectar cuántos meses de pagos te ahorras con cada abono extra.
                        </div>
                    @else
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-xs font-semibold text-zinc-300">Abono adicional ($):</span>
                            <input
                                type="number"
                                step="10"
                                wire:model.live.debounce.300ms="simulatorExtra"
                                class="w-32 bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm font-bold font-mono tabular-nums outline-none focus:border-indigo-500 min-h-[44px]"
                                placeholder="50.00"
                            />
                        </div>

                        @if (! empty($simulationResults))
                            <div class="bg-zinc-800/50 border border-zinc-700/50 rounded-xl p-3.5 text-xs space-y-1.5">
                                <div class="text-zinc-300">
                                    Deuda prioritaria objetivo: <strong class="text-white">{{ $simulationResults['targetDebtName'] }}</strong> (Saldo: ${{ number_format((float) $simulationResults['balance'], 2) }})
                                </div>
                                @if ($simulationResults['isImmediatePayoff'])
                                    <div class="text-emerald-400 font-semibold pt-1 flex items-start gap-1.5 font-mono">
                                        <svg class="w-4 h-4 shrink-0 text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <div>
                                            <div>¡Con este abono de ${{ number_format((float) $simulatorExtra, 2) }} liquidas el 100% de esta deuda de inmediato!</div>
                                            @if ($simulationResults['surplus'] > 0)
                                                <div class="text-xs text-zinc-400 font-sans font-normal pt-0.5">
                                                    Te sobran <strong class="text-emerald-300 font-mono">${{ number_format($simulationResults['surplus'], 2) }}</strong> para acelerar tu siguiente objetivo o liquidez.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif (($simulationResults['minPay'] ?? 0) > 0)
                                    <div class="text-zinc-400 font-mono tabular-nums">
                                        Pagando solo la cuota normal: <span class="text-zinc-200">{{ $simulationResults['monthsStandard'] }} {{ $simulationResults['monthsStandard'] === 1 ? 'mes' : 'meses' }}</span> ·
                                        Con abono acelerado: <strong class="text-emerald-400">{{ $simulationResults['monthsAccelerated'] }} {{ $simulationResults['monthsAccelerated'] === 1 ? 'mes' : 'meses' }}</strong>
                                    </div>
                                    <div class="text-emerald-400 font-semibold pt-1 flex items-center gap-1.5 font-mono tabular-nums">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>¡Te ahorras {{ $simulationResults['monthsSaved'] }} {{ $simulationResults['monthsSaved'] === 1 ? 'mes' : 'meses' }} de pagos en esta deuda!</span>
                                    </div>
                                @else
                                    <div class="text-emerald-400 font-semibold pt-0.5 flex items-center gap-1.5 font-mono tabular-nums">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>Abonando ${{ number_format((float) $simulatorExtra, 2) }}/mes la liquidas en aprox. {{ $simulationResults['monthsAccelerated'] }} {{ $simulationResults['monthsAccelerated'] === 1 ? 'mes' : 'meses' }}.</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </x-card>
        </div>

        {{-- Prioritized Debts List --}}
        <div class="space-y-3">
            <div>
                <h3 class="text-lg font-bold text-white">Pasivos Ordenados por Prioridad de Pago</h3>
                <p class="text-xs text-zinc-400">Orden de amortización según el algoritmo de la estrategia activa.</p>
            </div>

            <div class="space-y-3">
                @forelse ($sortedDebts as $index => $debt)
                    @php
                        $init = (float) $debt->initial_balance;
                        $curr = (float) $debt->current_balance;
                        $amortized = $init > 0 ? min(100, max(0, round(($init - $curr) / $init * 100, 1))) : 0;
                    @endphp
                    <x-card>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-indigo-600/30 text-indigo-300 font-bold text-xs flex items-center justify-center border border-indigo-500/40 font-mono tabular-nums">
                                        #{{ $index + 1 }}
                                    </span>
                                    <h4 class="font-extrabold text-base text-white">{{ $debt->name }}</h4>
                                    @if ($index === 0)
                                        <x-badge variant="neutral" size="sm" class="bg-amber-500/10 text-amber-300 border border-amber-500/30">
                                            Foco Principal Activo
                                        </x-badge>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-400 font-mono tabular-nums">
                                    {{ $debt->creditor ? 'Acreedor: ' . $debt->creditor . ' · ' : '' }}
                                    @if ((float) $debt->annual_interest_rate > 0 || (float) $debt->minimum_payment > 0)
                                        Tasa: <strong class="text-zinc-200">{{ number_format((float) $debt->annual_interest_rate, 2) }}% anual</strong> ·
                                        Cuota mínima: <strong class="text-zinc-200">${{ number_format((float) $debt->minimum_payment, 2) }}</strong>
                                    @else
                                        <span class="text-zinc-400">Préstamo directo (Sin intereses)</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="text-left sm:text-right">
                                    <div class="text-xs text-zinc-400">Saldo actual vivo</div>
                                    <div class="text-2xl font-extrabold text-white font-mono tabular-nums">
                                        ${{ number_format($curr, 2) }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="deleteDebt({{ $debt->id }})"
                                    wire:confirm="¿Estás seguro de eliminar este pasivo?"
                                    class="p-2 text-zinc-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-xl transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center"
                                    title="Eliminar pasivo"
                                    aria-label="Eliminar pasivo"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Amortization Progress Bar --}}
                        <div class="mt-4 space-y-1">
                            <div class="flex justify-between text-xs text-zinc-400 font-mono tabular-nums">
                                <span>Progreso de Amortización: {{ $amortized }}%</span>
                                <span>Inicial: ${{ number_format($init, 2) }}</span>
                            </div>
                            <div class="w-full bg-zinc-800 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-indigo-500 h-2.5 rounded-full transition-all duration-300" style="width: {{ $amortized }}%"></div>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <div class="text-center py-10 text-zinc-500 text-sm bg-zinc-900/40 rounded-2xl border border-zinc-800">
                        No hay pasivos registrados en este workspace.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Payment History --}}
        <div class="space-y-3">
            <div>
                <h3 class="text-lg font-bold text-white">Historial Reciente de Amortizaciones</h3>
                <p class="text-xs text-zinc-400">Registro auditable de pagos de capital efectuados a pasivos.</p>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-zinc-800 bg-zinc-900/40">
                <table class="w-full text-left text-sm text-zinc-300">
                    <thead class="bg-zinc-900/90 text-xs text-zinc-400 uppercase tracking-wider border-b border-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Deuda</th>
                            <th class="px-4 py-3 text-right font-mono">Principal</th>
                            <th class="px-4 py-3 text-right">Moneda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60 font-mono text-xs">
                        @forelse ($recentPayments as $payment)
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-2.5 font-sans text-zinc-400">{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5 font-sans font-semibold text-white">{{ $payment->debt?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2.5 text-right font-bold text-emerald-400 tabular-nums">${{ number_format((float) $payment->principal_amount, 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-sans text-zinc-400">{{ $payment->currency_code }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-zinc-500 font-sans">
                                    Aún no hay registros de amortización realizados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create Debt Modal --}}
    @if ($showDebtModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white">Registrar Nuevo Pasivo o Deuda</h3>
                    <button
                        wire:click="$toggle('showDebtModal')"
                        class="text-zinc-400 hover:text-white p-1 rounded-lg transition-colors"
                        aria-label="Cerrar modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="createDebt" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Nombre de la Deuda</label>
                        <x-input wire:model="debtName" placeholder="Ej: Deuda Leonel o Tarjeta Visa" />
                        @error('debtName') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto Total de la Deuda ($)</label>
                        <x-input wire:model="totalAmount" type="number" step="0.01" placeholder="Ej: 500.00" class="font-mono tabular-nums text-base" />
                        <p class="text-[11px] text-zinc-400 mt-1">Ingresa el total adeudado. Se guardará como saldo vivo actual.</p>
                        @error('totalAmount') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Acreedor / Prestamista (Opcional)</label>
                        <x-input wire:model="creditor" placeholder="Ej: Leonel Medina o Banco Santander" />
                    </div>

                    {{-- Advanced Options Toggle --}}
                    <div class="pt-1">
                        <button
                            type="button"
                            wire:click="$toggle('showAdvancedOptions')"
                            class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1.5 font-medium py-1"
                        >
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $showAdvancedOptions ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                            <span>{{ $showAdvancedOptions ? 'Ocultar opciones avanzadas' : '+ Opciones avanzadas (Tasa o cuota mensual opcional)' }}</span>
                        </button>

                        @if ($showAdvancedOptions)
                            <div class="grid grid-cols-2 gap-3 mt-3 p-3.5 bg-zinc-800/40 border border-zinc-800 rounded-xl">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Tasa Anual (%)</label>
                                    <x-input wire:model="interestRate" type="number" step="0.01" placeholder="0.00" class="font-mono tabular-nums" />
                                    <span class="text-[10px] text-zinc-400 mt-0.5 block">0% para préstamos personales</span>
                                    @error('interestRate') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuota Mínima ($)</label>
                                    <x-input wire:model="minimumPayment" type="number" step="0.01" placeholder="0.00" class="font-mono tabular-nums" />
                                    <span class="text-[10px] text-zinc-400 mt-0.5 block">Cuota mensual pactada</span>
                                    @error('minimumPayment') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-zinc-800/80">
                        <x-button wire:click="$toggle('showDebtModal')" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Guardar Pasivo
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bottom Navigation --}}
    <x-app-bottom-nav />
</div>
