<div class="min-h-screen-safe flex flex-col w-full max-w-full">
    {{-- Header --}}
    <header class="border-b border-zinc-800/80 bg-zinc-900/80 backdrop-blur-xl sticky top-0 z-30 pt-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-8 h-8 select-none">
                <span class="font-bold text-lg tracking-tight text-white select-none">Sistema de Objetivos Financieros</span>
            </div>

            <x-button wire:click="openCreateModal" variant="primary" class="min-h-[44px]">
                <span>+ Nueva Meta</span>
            </x-button>
        </div>

        {{-- Desktop Navigation Tabs & Live Rates Ticker --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between border-t border-zinc-800/60 text-sm gap-2 py-1 md:py-0">
            <div class="flex gap-4 overflow-x-auto no-scrollbar">
                <a href="{{ route('dashboard') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Panel General</a>
                <a href="{{ route('budget.sheet') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Hoja Presupuestaria</a>
                <a href="{{ route('budget.debts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Centro de Deudas</a>
                <a href="{{ route('budget.accounts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Cuentas y Tasas</a>
                <a href="{{ route('goals.index') }}" class="py-2.5 font-semibold text-indigo-400 border-b-2 border-indigo-500 whitespace-nowrap">Metas y Objetivos</a>
            </div>

            <div class="pb-2 md:pb-0">
                <x-exchange-rates-banner />
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 space-y-6">
        {{-- Flash Feedback Notification --}}
        @if ($feedbackMessage)
            <x-alert :variant="$feedbackType ?? 'info'" :dismissible="true">
                <span>{{ $feedbackMessage }}</span>
            </x-alert>
        @endif

        {{-- Financial Health Debt Shield Banner --}}
        @if ($overview->hasActiveUncoveredDebts)
            <x-alert variant="warning">
                <div class="space-y-1">
                    <div class="font-bold tracking-tight text-amber-200 flex items-center gap-2">
                        <span>ESCUDO DE SALUD FINANCIERA ACTIVO</span>
                        <x-badge variant="warning" size="xs">Prioridad Deudas</x-badge>
                    </div>
                    <p class="text-xs text-amber-300/90 leading-relaxed">
                        Tienes obligaciones de deuda activas o pagos mínimos pendientes en este período. El sistema bloquea temporalmente los aportes a sobres de ahorro para evitar fuga de capital y protegerte contra el cobro de intereses abusivos.
                    </p>
                </div>
            </x-alert>
        @endif

        {{-- Top Summary KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Acumulado en Sobres</div>
                        <div class="text-2xl font-black text-white font-mono mt-1 tabular-nums">
                            ${{ number_format((float) $overview->totalSavedInGoals, 2) }}
                        </div>
                    </div>
                    <div class="p-3 bg-emerald-950/60 border border-emerald-800/40 rounded-2xl text-emerald-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Metas en Marcha</div>
                        <div class="text-2xl font-black text-white font-mono mt-1 tabular-nums">
                            {{ $overview->activeGoalsCount }}
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-950/60 border border-indigo-800/40 rounded-2xl text-indigo-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Metas Completadas</div>
                        <div class="text-2xl font-black text-emerald-400 font-mono mt-1 tabular-nums">
                            {{ $overview->completedGoalsCount }}
                        </div>
                    </div>
                    <div class="p-3 bg-zinc-800/60 border border-zinc-700/40 rounded-2xl text-zinc-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Sinking Funds (Sobres de Ahorro para Compras) --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white tracking-tight">Sobres de Ahorro y Metas de Compra</h2>
                    <p class="text-xs text-zinc-400">Fondo acumulativo con cálculo de cuota mensual y fecha proyectada dinámica.</p>
                </div>
            </div>

            @if (empty($overview->sinkingFundGoals))
                <x-card>
                    <div class="text-center py-8 space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-zinc-800/80 border border-zinc-700/60 flex items-center justify-center text-zinc-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-white">No tienes metas de ahorro registradas</h3>
                        <p class="text-xs text-zinc-400 max-w-sm mx-auto">
                            Comienza fijando un objetivo tangible como una laptop, fondo de emergencia o vacaciones para que el sistema calcule tu cuota periódica.
                        </p>
                        <x-button wire:click="openCreateModal" variant="primary" size="sm">
                            Crear Primer Sobre
                        </x-button>
                    </div>
                </x-card>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($overview->sinkingFundGoals as $goal)
                        <x-card wire:key="goal-card-{{ $goal['id'] }}" class="relative overflow-hidden {{ $simulatingGoalId === $goal['id'] ? 'ring-2 ring-indigo-500/80' : '' }}">
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-white tracking-tight">{{ $goal['name'] }}</h3>
                                            @if ($goal['status'] === 'completed')
                                                <x-badge variant="success" size="xs">Completada</x-badge>
                                            @elseif ($goal['pacing_status'] === 'ahead')
                                                <x-badge variant="success" size="xs">Adelantada</x-badge>
                                            @elseif ($goal['pacing_status'] === 'delayed')
                                                <x-badge variant="warning" size="xs">Retrasada</x-badge>
                                            @else
                                                <x-badge variant="info" size="xs">En Ritmo</x-badge>
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-400 mt-0.5">
                                            Fecha Límite: <span class="font-medium text-zinc-300">{{ $goal['target_date'] }}</span>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-xl font-black text-white font-mono tabular-nums">
                                            ${{ number_format((float) $goal['current_amount'], 2) }}
                                        </div>
                                        <div class="text-xs text-zinc-400">
                                            de ${{ number_format((float) $goal['target_amount'], 2) }} {{ $goal['currency_code'] }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Progress bar --}}
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs text-zinc-400">
                                        <span>Progreso acumulado</span>
                                        <span class="font-bold text-indigo-300">{{ $goal['completion_percent'] }}%</span>
                                    </div>
                                    <x-progress :value="min(100, (float) $goal['completion_percent'])" max="100" variant="primary" />
                                </div>

                                {{-- Metric breakdown --}}
                                <div class="grid grid-cols-2 gap-3 p-3 bg-zinc-800/40 border border-zinc-800/80 rounded-xl text-xs">
                                    <div>
                                        <span class="text-zinc-400 block">Cuota mensual requerida:</span>
                                        <span class="font-bold text-white font-mono text-sm">${{ number_format((float) $goal['required_monthly_quota'], 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-400 block">Fecha estimada de compra:</span>
                                        <span class="font-bold {{ $goal['pacing_status'] === 'delayed' ? 'text-amber-400' : 'text-emerald-400' }} font-mono text-sm">
                                            {{ $goal['projected_date'] }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center justify-between gap-2 pt-2 border-t border-zinc-800/80">
                                    <x-button
                                        wire:click="selectGoalForSimulation({{ $goal['id'] }})"
                                        variant="neutral"
                                        size="sm"
                                        class="min-h-[44px]"
                                    >
                                        <svg class="w-4 h-4 mr-1 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.143 2.143L15.429 6" />
                                        </svg>
                                        <span>Simular What-If</span>
                                    </x-button>

                                    <x-button
                                        wire:click="openContributeModal({{ $goal['id'] }})"
                                        variant="primary"
                                        size="sm"
                                        class="min-h-[44px]"
                                        :disabled="$overview->hasActiveUncoveredDebts"
                                    >
                                        <span>Apartar Dinero</span>
                                    </x-button>
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Interactive What-If Simulation Playground Component --}}
        @if ($simulationResult && $simulatingGoalId)
            <div id="what-if-playground" class="scroll-mt-20">
                <x-card class="border-indigo-500/40 bg-zinc-900/95 shadow-xl">
                    <div class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-800 pb-3">
                            <div class="flex items-center gap-2">
                                <div class="p-2 rounded-xl bg-indigo-950/80 border border-indigo-700/60 text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white tracking-tight">Simulador Interactivo "What-If"</h3>
                                    <p class="text-xs text-zinc-400">Proyección en tiempo real: prueba aportes hipotéticos y observa cómo cambia tu fecha límite.</p>
                                </div>
                            </div>

                            @if ($simulationResult['is_accelerated'])
                                <x-badge variant="success" size="lg">
                                    ¡Adelanto de {{ abs($simulationResult['months_difference']) }} meses!
                                </x-badge>
                            @elseif ($simulationResult['is_delayed'])
                                <x-badge variant="warning" size="lg">
                                    Retraso de {{ abs($simulationResult['months_difference']) }} meses
                                </x-badge>
                            @else
                                <x-badge variant="info" size="lg">Fecha Estable</x-badge>
                            @endif
                        </div>

                        {{-- Simulator Reactive Inputs --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 mb-1">
                                    Aporte Mensual Simulado ($)
                                </label>
                                <x-input
                                    wire:model.live.debounce.300ms="simulatedMonthly"
                                    type="number"
                                    step="10"
                                    min="1"
                                    placeholder="100.00"
                                    class="font-mono tabular-nums text-lg font-bold"
                                />
                                <span class="text-[11px] text-zinc-400 mt-1 block">
                                    Cambia este valor para simular meses flojos ($100) o meses fuertes ($500).
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 mb-1">
                                    Aporte Extraordinario Inmediato ($)
                                </label>
                                <x-input
                                    wire:model.live.debounce.300ms="simulatedOneOff"
                                    type="number"
                                    step="50"
                                    min="0"
                                    placeholder="0.00"
                                    class="font-mono tabular-nums text-lg font-bold"
                                />
                                <span class="text-[11px] text-zinc-400 mt-1 block">
                                    ¿Qué pasa si entra un bono puntual hoy a la meta?
                                </span>
                            </div>
                        </div>

                        {{-- Simulation Comparison Results --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-zinc-950/70 border border-zinc-800 rounded-2xl text-center">
                            <div>
                                <div class="text-xs text-zinc-400">Fecha Proyectada Base</div>
                                <div class="text-base font-bold text-zinc-300 font-mono mt-0.5">
                                    {{ $simulationResult['baseline_completion_date'] }}
                                </div>
                                <div class="text-[11px] text-zinc-400">({{ $simulationResult['baseline_months'] }} meses)</div>
                            </div>

                            <div class="border-y sm:border-y-0 sm:border-x border-zinc-800/80 py-2 sm:py-0">
                                <div class="text-xs font-semibold text-indigo-300">Nueva Fecha Simulada</div>
                                <div class="text-lg font-black {{ $simulationResult['is_delayed'] ? 'text-amber-400' : 'text-emerald-400' }} font-mono mt-0.5">
                                    {{ $simulationResult['simulated_completion_date'] }}
                                </div>
                                <div class="text-[11px] font-medium {{ $simulationResult['is_delayed'] ? 'text-amber-300' : 'text-emerald-300' }}">
                                    ({{ $simulationResult['simulated_months'] }} meses)
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-zinc-400">Impacto en Calendario</div>
                                <div class="text-base font-black font-mono mt-0.5 {{ $simulationResult['is_delayed'] ? 'text-amber-400' : ($simulationResult['is_accelerated'] ? 'text-emerald-400' : 'text-zinc-300') }}">
                                    @if ($simulationResult['is_accelerated'])
                                        -{{ abs($simulationResult['months_difference']) }} meses
                                    @elseif ($simulationResult['is_delayed'])
                                        +{{ abs($simulationResult['months_difference']) }} meses
                                    @else
                                        Sin variación
                                    @endif
                                </div>
                                <div class="text-[11px] text-zinc-400">Respecto al plan original</div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        @endif
    </div>

    {{-- Create Goal Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-zinc-900 border border-zinc-800 rounded-3xl w-full max-w-lg p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white tracking-tight">Nueva Meta Financiera</h3>
                    <button wire:click="$toggle('showCreateModal')" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center">✕</button>
                </div>

                <form wire:submit="createGoal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Nombre del Sobre o Meta</label>
                        <x-input wire:model="name" placeholder="Ej. Laptop nueva M4, Fondo de Emergencia" />
                        @error('name') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto Objetivo ($)</label>
                            <x-input wire:model="targetAmount" type="number" step="0.01" placeholder="3000.00" class="font-mono tabular-nums" />
                            @error('targetAmount') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Fecha Límite</label>
                            <x-input wire:model="targetDate" type="date" />
                            @error('targetDate') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuota Mensual Planificada ($ opcional)</label>
                        <x-input wire:model="plannedMonthlyQuota" type="number" step="0.01" placeholder="Calculada automáticamente si está vacía" class="font-mono tabular-nums" />
                        <span class="text-[10px] text-zinc-400 mt-0.5 block">Si la dejas vacía, el sistema la calcula dividiendo entre los meses restantes.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Notas o Propósito (opcional)</label>
                        <x-textarea wire:model="notes" rows="2" placeholder="Detalles de la compra o cliente objetivo..." />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-zinc-800">
                        <x-button wire:click="$toggle('showCreateModal')" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Guardar Meta
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Contribute to Goal Modal --}}
    @if ($showContributeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-zinc-900 border border-zinc-800 rounded-3xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white tracking-tight">Apartar Dinero en Sobre</h3>
                    <button wire:click="$toggle('showContributeModal')" class="text-zinc-400 hover:text-white p-2 min-h-[44px] min-w-[44px] flex items-center justify-center">✕</button>
                </div>

                <form wire:submit="contribute" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto a Apartar ($)</label>
                        <x-input
                            wire:model="contributionAmount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="100.00"
                            class="font-mono tabular-nums text-lg font-bold"
                            autofocus
                        />
                        @error('contributionAmount') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Nota del Aporte (opcional)</label>
                        <x-input wire:model="contributionNotes" placeholder="Ej. Ahorro de la primera quincena" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-zinc-800">
                        <x-button wire:click="$toggle('showContributeModal')" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Confirmar Aporte
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Mobile Bottom Navigation --}}
    <x-app-bottom-nav />
</div>
