<div class="w-full">
    {{-- Banner bar --}}
    <div
        wire:click="toggleBreakdownModal"
        role="button"
        tabindex="0"
        aria-label="Ver desglose de liquidez y capital"
        class="group w-full bg-zinc-900/90 hover:bg-zinc-800/80 border border-zinc-800/80 hover:border-zinc-700/80 rounded-2xl p-3 sm:px-5 sm:py-3.5 transition-all cursor-pointer shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 select-none"
    >
        {{-- Left: Net Liquidity Indicator --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ bccomp($metrics->netLiquidity, '0', 2) >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">
                    Liquidez y Capital Disponible
                </div>
                <div class="text-lg sm:text-xl font-bold font-mono tracking-tight tabular-nums {{ bccomp($metrics->netLiquidity, '0', 2) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    ${{ number_format((float) $metrics->netLiquidity, 2) }}
                </div>
            </div>
        </div>

        {{-- Center: Flow Equation --}}
        <div class="flex items-center gap-2 sm:gap-4 text-xs font-mono">
            <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl px-3 py-1.5 flex items-center gap-1.5">
                <span class="text-zinc-400">Entradas:</span>
                <span class="font-bold text-emerald-400 tabular-nums">+${{ number_format((float) $metrics->totalInflows, 2) }}</span>
            </div>
            <span class="text-zinc-600 font-bold">−</span>
            <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl px-3 py-1.5 flex items-center gap-1.5">
                <span class="text-zinc-400">Gastos del Mes:</span>
                <span class="font-bold text-rose-400 tabular-nums">-${{ number_format((float) $metrics->paidExpenses, 2) }}</span>
            </div>
        </div>

        {{-- Right: CTA Button --}}
        <div class="flex items-center justify-end gap-1.5 text-xs font-semibold text-indigo-400 group-hover:text-indigo-300 transition-colors">
            <span>Ver Desglose</span>
            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    </div>

    {{-- Breakdown Modal --}}
    @if ($showBreakdownModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-white">Desglose de Liquidez y Capital</h3>
                        <p class="text-xs text-zinc-400 mt-0.5">Auditoría en tiempo real de entradas y egresos deducidos.</p>
                    </div>
                    <button
                        wire:click="toggleBreakdownModal"
                        class="text-zinc-400 hover:text-white p-1.5 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center"
                        aria-label="Cerrar modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Section 1: Inflows (4 Pillars) --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-400">
                        <span>Fuentes de Capital (Entradas)</span>
                        <span class="font-mono text-emerald-400 font-bold tabular-nums">+${{ number_format((float) $metrics->totalInflows, 2) }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        {{-- 1. Sueldo --}}
                        <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-white">Sueldo</div>
                                    <div class="text-[10px] text-zinc-400">Remuneración Mensual</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold font-mono text-white tabular-nums">
                                ${{ number_format((float) $metrics->salary, 2) }}
                            </div>
                        </div>

                        {{-- 2. Side Projects --}}
                        <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.458 14.96 14.96 0 0 1-.06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-white">Side Projects</div>
                                    <div class="text-[10px] text-zinc-400">Emprendimientos</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold font-mono text-white tabular-nums">
                                ${{ number_format((float) $metrics->sideProjects, 2) }}
                            </div>
                        </div>

                        {{-- 3. Inversiones --}}
                        <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-white">Inversiones</div>
                                    <div class="text-[10px] text-zinc-400">Rendimientos</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold font-mono text-white tabular-nums">
                                ${{ number_format((float) $metrics->investments, 2) }}
                            </div>
                        </div>

                        {{-- 4. Ahorro Mes Anterior --}}
                        <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A48.24 48.24 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.583V21" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-white">Ahorro Mes Anterior</div>
                                    <div class="text-[10px] text-zinc-400">{{ $metrics->previousPeriodLabel ?? 'Remanente neto' }}</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold font-mono text-emerald-400 tabular-nums">
                                ${{ number_format((float) $metrics->previousPeriodSavings, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Deductions (Expenses) --}}
                <div class="space-y-2 pt-2 border-t border-zinc-800/80">
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-400">
                        <span>Deducciones (Gastos del Mes)</span>
                        <span class="font-mono text-rose-400 font-bold tabular-nums">-${{ number_format((float) $metrics->paidExpenses, 2) }}</span>
                    </div>

                    <div class="space-y-2">
                        {{-- Gastos Pagados (Deducidos) --}}
                        <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-white">Gastos Pagados (Deducidos)</div>
                                    <div class="text-[10px] text-zinc-400">Egresos ejecutados y descontados</div>
                                </div>
                            </div>
                            <div class="text-sm font-bold font-mono text-rose-400 tabular-nums">
                                -${{ number_format((float) $metrics->paidExpenses, 2) }}
                            </div>
                        </div>

                        {{-- Gastos Pendientes (Informativo, no restado aún de la liquidez) --}}
                        @if (bccomp($metrics->pendingExpenses, '0', 2) > 0)
                            <div class="bg-zinc-950/40 border border-amber-500/20 rounded-xl p-3 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-zinc-300">Compromisos Pendientes</div>
                                        <div class="text-[10px] text-amber-400/80">Por pagar (no resta de liquidez hasta pagar)</div>
                                    </div>
                                </div>
                                <div class="text-sm font-bold font-mono text-zinc-400 tabular-nums">
                                    ${{ number_format((float) $metrics->pendingExpenses, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Section 3: Net Liquidity Result --}}
                <div class="p-4 rounded-xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Total Liquidez Disponible</div>
                        <div class="text-[11px] text-zinc-500">Capital real después de cubrir todos los egresos</div>
                    </div>
                    <div class="text-xl font-bold font-mono tabular-nums {{ bccomp($metrics->netLiquidity, '0', 2) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        ${{ number_format((float) $metrics->netLiquidity, 2) }}
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex justify-end pt-2 border-t border-zinc-800">
                    <x-button wire:click="toggleBreakdownModal" type="button" variant="neutral" class="min-h-[44px]">
                        Cerrar
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>
