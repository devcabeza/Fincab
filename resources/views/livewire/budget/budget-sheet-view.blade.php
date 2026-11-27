<div class="min-h-screen-safe flex flex-col w-full max-w-full">
    {{-- Header --}}
    <header class="border-b border-zinc-800/80 bg-zinc-900/80 backdrop-blur-xl sticky top-0 z-30 pt-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-8 h-8 select-none">
                <span class="font-bold text-lg tracking-tight text-white select-none">Hoja Presupuestaria</span>
            </div>

            <x-button wire:click="openCreateModal" variant="primary" class="min-h-[44px]">
                <span>+ Nueva Partida</span>
            </x-button>
        </div>

        {{-- Desktop Navigation Tabs & Live Rates Ticker --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between border-t border-zinc-800/60 text-sm gap-2 py-1 md:py-0">
            <div class="flex gap-4 overflow-x-auto no-scrollbar">
                <a href="{{ route('dashboard') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Panel General</a>
                <a href="{{ route('budget.sheet') }}" class="py-2.5 font-semibold text-indigo-400 border-b-2 border-indigo-500 whitespace-nowrap">Hoja Presupuestaria</a>
                <a href="{{ route('budget.debts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Centro de Deudas</a>
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
        @if ($feedbackMessage)
            <x-alert variant="success">
                <span>{{ $feedbackMessage }}</span>
            </x-alert>
        @endif

        {{-- Global Liquidity & Capital Banner --}}
        <livewire:budget.global-liquidity-banner :workspace-id="$selectedWorkspaceId" :period-id="$selectedPeriodId" wire:key="budget-global-liquidity-banner" />

        {{-- Filter Controls --}}
        <div class="flex flex-wrap items-center justify-between gap-4 bg-zinc-900/60 border border-zinc-800 p-4 rounded-2xl">
            {{-- Type and Quincena Filter Pills --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- Type Filter (Todos / Gastos / Ingresos) --}}
                <div class="flex items-center gap-1.5 bg-zinc-950/60 p-1 rounded-xl border border-zinc-800">
                    <button
                        wire:click="$set('typeFilter', 'all')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold min-h-[36px] transition-colors {{ $typeFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-400 hover:text-white' }}"
                    >
                        Todos
                    </button>
                    <button
                        wire:click="$set('typeFilter', 'expense')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold min-h-[36px] transition-colors {{ $typeFilter === 'expense' ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-400 hover:text-white' }}"
                    >
                        Gastos
                    </button>
                    <button
                        wire:click="$set('typeFilter', 'income')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold min-h-[36px] transition-colors {{ $typeFilter === 'income' ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-400 hover:text-white' }}"
                    >
                        Ingresos
                    </button>
                </div>

                {{-- Quincena Filter Pills --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-400 mr-1">Quincena:</span>
                    @if ($quincenaFilter === 'all')
                        <button wire:click="$set('quincenaFilter', 'all')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-indigo-600 text-white shadow-sm">Todas</button>
                    @else
                        <button wire:click="$set('quincenaFilter', 'all')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-zinc-800 text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors">Todas</button>
                    @endif

                    @if ($quincenaFilter === 'q1')
                        <button wire:click="$set('quincenaFilter', 'q1')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-indigo-600 text-white shadow-sm">Primera (Q1: 1–15)</button>
                    @else
                        <button wire:click="$set('quincenaFilter', 'q1')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-zinc-800 text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors">Primera (Q1: 1–15)</button>
                    @endif

                    @if ($quincenaFilter === 'q2')
                        <button wire:click="$set('quincenaFilter', 'q2')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-indigo-600 text-white shadow-sm">Segunda (Q2: 16–30)</button>
                    @else
                        <button wire:click="$set('quincenaFilter', 'q2')" class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] bg-zinc-800 text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors">Segunda (Q2: 16–30)</button>
                    @endif
                </div>
            </div>

            {{-- Secondary Filters: Recurring & Bucket Category --}}
            <div class="flex flex-wrap items-center gap-3">
                @if ($recurringFilter === 'recurring')
                    <button
                        wire:click="$set('recurringFilter', 'all')"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] flex items-center gap-2 bg-indigo-600 text-white shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Solo Recurrentes</span>
                    </button>
                @else
                    <button
                        wire:click="$set('recurringFilter', 'recurring')"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold min-h-[44px] flex items-center gap-2 bg-zinc-800 text-zinc-300 hover:bg-zinc-700 hover:text-white transition-colors"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Solo Recurrentes</span>
                    </button>
                @endif

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-400 mr-1">Bloque:</span>
                    <select wire:model.live="bucketFilter" class="bg-zinc-800 border border-zinc-700 text-white text-xs rounded-xl px-3 py-2 min-h-[44px] outline-none focus:border-indigo-500 cursor-pointer">
                        <option value="all">Todos los Bloques</option>
                        @foreach ($buckets as $b)
                            <option value="{{ $b->key }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Spreadsheet Table --}}
        <div class="overflow-x-auto rounded-2xl border border-zinc-800 bg-zinc-900/40">
            <table class="w-full text-left text-sm text-zinc-300">
                <thead class="bg-zinc-900/90 text-xs text-zinc-400 uppercase tracking-wider border-b border-zinc-800">
                    <tr>
                        <th class="px-4 py-3.5">Partida / Concepto</th>
                        <th class="px-4 py-3.5">Bloque</th>
                        <th class="px-4 py-3.5 text-right font-mono">Q1 (1–15)</th>
                        <th class="px-4 py-3.5 text-right font-mono">Q2 (16–30)</th>
                        <th class="px-4 py-3.5 text-right font-mono">Total ($)</th>
                        <th class="px-4 py-3.5 text-center">Estado (1-Clic)</th>
                        <th class="px-4 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 font-mono text-xs sm:text-sm">
                    @forelse ($items as $item)
                        @php
                            $totalAmount = bcadd((string) $item->amount_q1, (string) $item->amount_q2, 2);
                        @endphp
                        <tr wire:key="item-row-{{ $item->id }}" class="hover:bg-zinc-800/40 transition-colors">
                            <td class="px-4 py-3 font-sans">
                                <div class="font-semibold text-white flex items-center gap-2">
                                    <span>{{ $item->title }}</span>
                                    @if ($item->item_type === 'income')
                                        @if ($item->income_category === 'salary')
                                            <span class="text-[10px] bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 px-1.5 py-0.5 rounded font-sans font-medium">Sueldo</span>
                                        @elseif ($item->income_category === 'side_projects')
                                            <span class="text-[10px] bg-cyan-500/10 text-cyan-300 border border-cyan-500/30 px-1.5 py-0.5 rounded font-sans font-medium">Side Project</span>
                                        @elseif ($item->income_category === 'investments')
                                            <span class="text-[10px] bg-amber-500/10 text-amber-300 border border-amber-500/30 px-1.5 py-0.5 rounded font-sans font-medium">Inversión</span>
                                        @else
                                            <span class="text-[10px] bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.5 rounded font-sans font-medium">Ingreso</span>
                                        @endif
                                    @endif
                                    @if ($item->account)
                                        <span class="text-[10px] bg-zinc-800 text-zinc-300 border border-zinc-700 px-1.5 py-0.5 rounded font-sans flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                            </svg>
                                            {{ $item->account->name }}
                                        </span>
                                    @endif
                                    @if ($item->is_recurring)
                                        <span class="text-[10px] bg-indigo-500/10 text-indigo-300 px-1.5 py-0.5 rounded border border-indigo-500/30 flex items-center gap-1 font-sans">
                                            <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            Recurrente
                                        </span>
                                    @endif
                                    @if ($item->minimum_payment_protected)
                                        <span class="text-[10px] bg-amber-500/10 text-amber-300 border border-amber-500/30 px-1.5 py-0.5 rounded font-sans">Mínimo Protegido</span>
                                    @endif
                                </div>
                                @if ($item->debt)
                                    <div class="text-xs text-indigo-400 mt-0.5 font-mono tabular-nums">
                                        Pasivo: {{ $item->debt->name }} (Saldo: ${{ number_format((float) $item->debt->current_balance, 2) }})
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-sans text-xs text-zinc-400">
                                @if ($item->item_type === 'income')
                                    <span class="text-emerald-400/90 font-medium">
                                        @if ($item->income_category === 'salary') Sueldo Mensual
                                        @elseif ($item->income_category === 'side_projects') Emprendimiento
                                        @elseif ($item->income_category === 'investments') Rendimiento
                                        @else Ingreso
                                        @endif
                                    </span>
                                @else
                                    {{ $item->budgetBucket?->name ?? 'General' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $item->item_type === 'income' ? 'text-emerald-300' : 'text-zinc-300' }}">
                                ${{ number_format((float) $item->amount_q1, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $item->item_type === 'income' ? 'text-emerald-300' : 'text-zinc-300' }}">
                                ${{ number_format((float) $item->amount_q2, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums {{ $item->item_type === 'income' ? 'text-emerald-400' : 'text-white' }}">
                                {{ $item->item_type === 'income' ? '+' : '-' }}${{ number_format((float) $totalAmount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center font-sans">
                                <button
                                    wire:click="toggleStatus({{ $item->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all min-h-[36px] cursor-pointer {{ $item->status === 'paid' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30' }}"
                                >
                                    @if ($item->status === 'paid')
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>Pagado</span>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Pendiente</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right font-sans">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        wire:click="openEditModal({{ $item->id }})"
                                        class="p-2 text-zinc-400 hover:text-white transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800"
                                        title="Editar"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="deleteItem({{ $item->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar esta partida?"
                                        class="p-2 text-zinc-400 hover:text-rose-400 transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-zinc-800"
                                        title="Eliminar"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-zinc-500 font-sans">
                                No se encontraron partidas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if ($showItemModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-800">
                    <h3 class="text-lg font-bold text-white">
                        {{ $editingItemId ? 'Editar Partida Presupuestaria' : 'Nueva Partida Presupuestaria' }}
                    </h3>
                    <button
                        wire:click="$set('showItemModal', false)"
                        class="text-zinc-400 hover:text-white p-1 rounded-lg transition-colors"
                        aria-label="Cerrar modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveItem" class="space-y-4">
                    {{-- 1. Concept / Title --}}
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Título / Concepto</label>
                        <x-input wire:model="title" placeholder="Ej: Pago de Luz, Alquiler, Servidor Contabo" />
                        @error('title') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- 2. Total Amount with Live Breakdown, Quincena Presets, and Currency Toggle --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-zinc-300">Monto Total ($ USD)</label>
                            <button
                                type="button"
                                wire:click="$toggle('showCurrencyConverter')"
                                class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1 font-medium py-0.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                                <span>{{ $showCurrencyConverter ? 'Cerrar conversor' : 'Calcular en Bs / COP' }}</span>
                            </button>
                        </div>

                        <x-input wire:model.live.debounce.250ms="totalAmount" type="number" step="0.01" placeholder="Ej: 50.00" class="font-mono tabular-nums text-base" />
                        @error('totalAmount') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror

                        {{-- Distribution Presets & Live Split Indicators --}}
                        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                            <div class="inline-flex rounded-xl p-0.5 bg-zinc-800/80 border border-zinc-700/60 text-xs">
                                <button
                                    type="button"
                                    wire:click="setDistribution('equal')"
                                    class="px-2.5 py-1.5 rounded-lg font-medium transition-colors {{ $splitMode === 'equal' ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-300 hover:text-white' }}"
                                >
                                    50% / 50%
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDistribution('q1_only')"
                                    class="px-2.5 py-1.5 rounded-lg font-medium transition-colors {{ $splitMode === 'manual' && bccomp($amountQ2, '0', 2) === 0 && bccomp($amountQ1, '0', 2) > 0 ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-300 hover:text-white' }}"
                                >
                                    Solo Q1 (1–15)
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDistribution('q2_only')"
                                    class="px-2.5 py-1.5 rounded-lg font-medium transition-colors {{ $splitMode === 'manual' && bccomp($amountQ1, '0', 2) === 0 && bccomp($amountQ2, '0', 2) > 0 ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-300 hover:text-white' }}"
                                >
                                    Solo Q2 (16–30)
                                </button>
                            </div>

                            @if ($totalAmount && is_numeric($totalAmount) && (float) $totalAmount > 0)
                                <span class="text-xs font-mono text-zinc-300 bg-zinc-800/60 px-2.5 py-1 rounded-lg border border-zinc-700/50 tabular-nums">
                                    Q1: <strong class="text-indigo-400">${{ number_format((float) $amountQ1, 2) }}</strong> · Q2: <strong class="text-indigo-400">${{ number_format((float) $amountQ2, 2) }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Collapsible Currency Converter --}}
                    @if ($showCurrencyConverter)
                        <div class="p-3 bg-zinc-950/80 rounded-xl border border-zinc-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-semibold text-zinc-300">Moneda de origen</label>
                                <span class="text-[10px] text-zinc-400">Conversión en vivo a USD</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['VES' => 'VES (Bolívares)', 'COP' => 'COP (Pesos)'] as $cCode => $cLabel)
                                    <button
                                        type="button"
                                        wire:click="$set('currency', '{{ $cCode }}')"
                                        class="py-2 px-2 rounded-xl text-xs font-semibold border min-h-[44px] transition-colors {{ $currency === $cCode ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-zinc-700 bg-zinc-800 text-zinc-300 hover:text-white' }}"
                                    >
                                        {{ $cLabel }}
                                    </button>
                                @endforeach
                            </div>

                            @if ($currency === 'VES')
                                <div class="pt-1 space-y-2">
                                    <label class="block text-[11px] font-semibold text-zinc-400">Tasa de cambio</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[11px]">
                                        <button type="button" wire:click="selectRateSource('bcv')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'bcv' ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            BCV: {{ $bcvQuote ? number_format((float) $bcvQuote, 2) : '922.56' }}
                                        </button>
                                        <button type="button" wire:click="selectRateSource('paralelo')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'paralelo' ? 'border-amber-500 bg-amber-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            Paralelo: {{ $paraleloQuote ? number_format((float) $paraleloQuote, 2) : '1104.78' }}
                                        </button>
                                        <button type="button" wire:click="selectRateSource('binance')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            P2P: {{ $binanceVesQuote ? number_format((float) $binanceVesQuote, 2) : '1105.00' }}
                                        </button>
                                        <button type="button" wire:click="selectRateSource('manual')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            Manual
                                        </button>
                                    </div>

                                    @if ($rateSource === 'manual')
                                        <div>
                                            <label class="block text-[11px] text-zinc-400 mb-1">Tasa Manual (1 USD = ? Bs)</label>
                                            <x-input wire:model.live.debounce.250ms="manualQuote" type="number" step="0.01" placeholder="Ej: 950.00" />
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto en Bolívares (VES)</label>
                                        <x-input wire:model.live.debounce.250ms="localAmount" type="number" step="0.01" placeholder="Ej: 1500.00" />
                                    </div>
                                </div>
                            @elseif ($currency === 'COP')
                                <div class="pt-1 space-y-2">
                                    <label class="block text-[11px] font-semibold text-zinc-400">Tasa de cambio</label>
                                    <div class="grid grid-cols-3 gap-1.5 text-[11px]">
                                        <button type="button" wire:click="selectRateSource('official')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'official' ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            TRM: {{ $copQuote ? number_format((float) $copQuote, 0) : '3604' }}
                                        </button>
                                        <button type="button" wire:click="selectRateSource('binance')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            P2P: {{ $copBinanceQuote ? number_format((float) $copBinanceQuote, 0) : '3468' }}
                                        </button>
                                        <button type="button" wire:click="selectRateSource('manual')" class="py-2 px-1.5 rounded-lg border text-center font-bold {{ $rateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-zinc-700 bg-zinc-800/80 text-zinc-300 hover:text-white' }}">
                                            Manual
                                        </button>
                                    </div>

                                    @if ($rateSource === 'manual')
                                        <div>
                                            <label class="block text-[11px] text-zinc-400 mb-1">Tasa Manual (1 USD = ? COP)</label>
                                            <x-input wire:model.live.debounce.250ms="manualQuote" type="number" step="0.01" placeholder="Ej: 3600.00" />
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto en Pesos Colombianos (COP)</label>
                                        <x-input wire:model.live.debounce.250ms="localAmount" type="number" step="0.01" placeholder="Ej: 80000.00" />
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- 3. Account and Category in 2 columns --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuenta de Pago</label>
                            <select wire:model.live="accountId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                                <option value="">Sin cuenta vinculada</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->currency_code }}) · {{ $acc->currency_code === 'USD' ? '$' . number_format((float) $acc->opening_balance, 2) : number_format((float) $acc->opening_balance, 2) . ' ' . $acc->currency_code }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($itemType === 'income')
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 mb-1">Fuente de Ingreso</label>
                                <select wire:model="incomeCategory" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                                    <option value="salary">Sueldo (Remuneración Mensual)</option>
                                    <option value="side_projects">Side Projects (Emprendimientos)</option>
                                    <option value="investments">Inversiones (Rendimientos / Dividendos)</option>
                                </select>
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 mb-1">Categoría</label>
                                <select wire:model="bucketId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                                    <option value="">Sin Bloque</option>
                                    @foreach ($buckets as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    {{-- Dedicated Rate Configuration Box for Non-USD Account --}}
                    @php
                        $selectedAcc = $accounts->firstWhere('id', $accountId);
                    @endphp
                    @if ($selectedAcc && $selectedAcc->currency_code !== 'USD')
                        <div class="p-3.5 bg-zinc-950/90 border border-indigo-500/40 rounded-xl space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-indigo-300 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Tasa de cambio para {{ $selectedAcc->name }} ({{ $selectedAcc->currency_code }})</span>
                                </span>
                                <span class="text-[11px] text-zinc-400 font-mono">
                                    1 USD = {{ $this->getActiveExchangeRate() ? number_format((float) $this->getActiveExchangeRate(), 2) : '1.00' }} {{ $selectedAcc->currency_code }}
                                </span>
                            </div>

                            @if ($selectedAcc->currency_code === 'VES')
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-xs">
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('bcv')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'bcv' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        BCV: {{ $bcvQuote ? number_format((float) $bcvQuote, 2) : '924.35' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('paralelo')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'paralelo' ? 'border-amber-500 bg-amber-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Paralelo: {{ $paraleloQuote ? number_format((float) $paraleloQuote, 2) : '1095.44' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('binance')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Binance P2P: {{ $binanceVesQuote ? number_format((float) $binanceVesQuote, 2) : '1090.00' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('manual')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Personalizada
                                    </button>
                                </div>
                            @elseif ($selectedAcc->currency_code === 'COP')
                                <div class="grid grid-cols-3 gap-1.5 text-xs">
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('official')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'official' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        TRM: {{ $copQuote ? number_format((float) $copQuote, 0) : '3604' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('binance')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'binance' ? 'border-yellow-500 bg-yellow-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Binance: {{ $copBinanceQuote ? number_format((float) $copBinanceQuote, 0) : '3468' }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="selectRateSource('manual')"
                                        class="py-2 px-1.5 rounded-lg border text-center font-bold transition-all min-h-[38px] {{ $rateSource === 'manual' ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm' : 'border-zinc-700 bg-zinc-800/90 text-zinc-300 hover:text-white' }}"
                                    >
                                        Personalizada
                                    </button>
                                </div>
                            @endif

                            @if ($rateSource === 'manual')
                                <div class="pt-1">
                                    <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Ingresa tu tasa personalizada (1 USD = ? {{ $selectedAcc->currency_code }}):</label>
                                    <x-input wire:model.live.debounce.250ms="manualQuote" type="number" step="0.01" placeholder="Ej: 950.00" class="text-xs font-mono" />
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-xs bg-zinc-900/80 p-2.5 rounded-xl border border-zinc-800 font-mono">
                                <span class="text-zinc-400">Débito estimado en cuenta:</span>
                                <span class="font-bold text-emerald-400">
                                    @php
                                        $activeRateVal = $this->getActiveExchangeRate();
                                    @endphp
                                    @if ($totalAmount && is_numeric($totalAmount) && (float) $totalAmount > 0 && $activeRateVal && (float) $activeRateVal > 0)
                                        {{ number_format((float) bcmul((string) $totalAmount, (string) $activeRateVal, 2), 2) }} {{ $selectedAcc->currency_code }}
                                    @else
                                        0.00 {{ $selectedAcc->currency_code }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- 4. Type & Recurring Row --}}
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div class="w-1/2">
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Tipo</label>
                            <select wire:model.live="itemType" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                                <option value="expense">Gasto</option>
                                <option value="income">Ingreso</option>
                            </select>
                        </div>

                        <div class="w-1/2 pt-5 flex items-center gap-2">
                            <input type="checkbox" wire:model="isRecurring" id="isRecurring" class="w-4 h-4 rounded bg-zinc-800 border-zinc-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="isRecurring" class="text-xs text-zinc-300 cursor-pointer select-none">{{ $itemType === 'income' ? 'Ingreso mensual recurrente' : 'Gasto mensual recurrente' }}</label>
                        </div>
                    </div>

                    {{-- 5. Collapsible Advanced Options --}}
                    <div class="pt-1">
                        <button
                            type="button"
                            wire:click="$toggle('showAdvancedOptions')"
                            class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1.5 font-medium py-1"
                        >
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ $showAdvancedOptions ? 'rotate-90' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                            <span>{{ $showAdvancedOptions ? 'Ocultar opciones avanzadas' : '+ Opciones avanzadas (Vincular deuda, quincenas manuales...)' }}</span>
                        </button>

                        @if ($showAdvancedOptions)
                            <div class="mt-3 p-3.5 bg-zinc-800/40 border border-zinc-800 rounded-xl space-y-3">
                                @if ($itemType === 'expense')
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Vincular a Pasivo / Deuda (Opcional)</label>
                                        <select wire:model="debtId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm outline-none focus:border-indigo-500 cursor-pointer min-h-[44px]">
                                            <option value="">Ninguna</option>
                                            @foreach ($debts as $d)
                                                <option value="{{ $d->id }}">{{ $d->name }} (Mínimo: ${{ number_format((float) $d->minimum_payment, 2) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Primera Quincena (Q1)</label>
                                        <x-input wire:model.live.debounce.300ms="amountQ1" type="number" step="0.01" />
                                        @error('amountQ1') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Segunda Quincena (Q2)</label>
                                        <x-input wire:model.live.debounce.300ms="amountQ2" type="number" step="0.01" />
                                        @error('amountQ2') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 mb-1">Justificación (Opcional si Ocio está bloqueado)</label>
                                    <x-input wire:model="justification" placeholder="Razón justificable para desbloqueo" />
                                    @error('justification') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2 pt-2 border-t border-zinc-800/80">
                        <x-button wire:click="$set('showItemModal', false)" type="button" variant="neutral" class="min-h-[44px]">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary" class="min-h-[44px]">
                            Guardar Partida
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bottom Navigation --}}
    <x-app-bottom-nav />
</div>
