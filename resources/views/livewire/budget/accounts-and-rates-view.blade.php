<div class="min-h-screen-safe flex flex-col w-full max-w-full">
    {{-- Header --}}
    <header class="border-b border-zinc-800/80 bg-zinc-900/80 backdrop-blur-xl sticky top-0 z-30 pt-safe">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-8 h-8 select-none">
                <span class="font-bold text-lg tracking-tight text-white select-none">Cuentas y Métodos de Pago</span>
            </div>

            <div class="flex items-center gap-2">
                <x-button wire:click="openCreateAccountModal" variant="primary" class="min-h-[40px]">
                    <span>+ Nueva Cuenta</span>
                </x-button>
                <x-button wire:click="$toggle('showTransferModal')" variant="neutral" class="bg-zinc-800 text-white min-h-[40px]">
                    <span>Transferir entre Cuentas</span>
                </x-button>
            </div>
        </div>

        {{-- Desktop Navigation Tabs & Live Rates Ticker --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between border-t border-zinc-800/60 text-sm gap-2 py-1 md:py-0">
            <div class="flex gap-4 overflow-x-auto no-scrollbar">
                <a href="{{ route('dashboard') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Panel General</a>
                <a href="{{ route('budget.sheet') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Hoja Presupuestaria</a>
                <a href="{{ route('budget.debts') }}" class="py-2.5 text-zinc-400 hover:text-white transition-colors whitespace-nowrap">Centro de Deudas</a>
                <a href="{{ route('budget.accounts') }}" class="py-2.5 font-semibold text-indigo-400 border-b-2 border-indigo-500 whitespace-nowrap">Cuentas y Tasas</a>
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
        <livewire:budget.global-liquidity-banner :workspace-id="$selectedWorkspaceId" wire:key="accounts-global-liquidity-banner" />

        {{-- Accounts Section --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">Métodos de Pago y Cuentas</h3>
                    <p class="text-xs text-zinc-400">Bancos, billeteras digitales y efectivo registrados en este workspace.</p>
                </div>
                <x-button wire:click="openCreateAccountModal" variant="primary" size="sm" class="sm:hidden">
                    <span>+ Nueva Cuenta</span>
                </x-button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($accounts as $account)
                    <x-card>
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-bold text-white text-base">{{ $account->name }}</div>
                                <div class="text-xs text-zinc-400">Moneda: {{ $account->currency_code }}</div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <x-badge variant="neutral" size="sm" class="bg-zinc-800 text-zinc-300">
                                    {{ $account->currency_code }}
                                </x-badge>
                                <button
                                    wire:click="openEditAccountModal({{ $account->id }})"
                                    class="p-1 text-zinc-400 hover:text-white transition-colors"
                                    title="Editar Cuenta"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="deleteAccount({{ $account->id }})"
                                    wire:confirm="¿Seguro que deseas eliminar esta cuenta?"
                                    class="p-1 text-zinc-400 hover:text-rose-400 transition-colors"
                                    title="Eliminar Cuenta"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-baseline">
                            <span class="text-xs text-zinc-400">Saldo vivo:</span>
                            <span class="text-xl font-extrabold text-emerald-400 font-mono">
                                ${{ number_format((float) $account->opening_balance, 2) }}
                            </span>
                        </div>
                    </x-card>
                @empty
                    <div class="col-span-full text-center py-10 text-zinc-500 text-sm bg-zinc-900/40 rounded-2xl border border-zinc-800">
                        <p class="mb-3">No hay cuentas registradas en este momento.</p>
                        <x-button wire:click="openCreateAccountModal" variant="primary" size="sm">
                            <span>+ Crear tu primera cuenta</span>
                        </x-button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Transfers Ledger --}}
        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-white">Registro de Movimientos y Transferencias</h3>
                    <p class="text-xs text-zinc-400">Historial auditable de egresos, ingresos y transferencias por cuenta.</p>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-xs text-zinc-400 whitespace-nowrap">Filtrar por cuenta:</label>
                    <select wire:model.live="filterAccountId" class="bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-1.5 text-xs outline-none focus:border-indigo-500 cursor-pointer min-h-[38px]">
                        <option value="">Todas las cuentas</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->currency_code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-zinc-800 bg-zinc-900/40">
                <table class="w-full text-left text-sm text-zinc-300">
                    <thead class="bg-zinc-900/90 text-xs text-zinc-400 uppercase tracking-wider border-b border-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Concepto / Partida</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/60 font-mono text-xs">
                        @forelse ($recentTransactions as $tx)
                            <tr class="hover:bg-zinc-800/30">
                                <td class="px-4 py-2.5 font-sans text-zinc-400">{{ $tx->occurred_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5 font-sans font-semibold">
                                    @if ($tx->transaction_type === 'income')
                                        <span class="inline-flex items-center gap-1 text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75" />
                                            </svg>
                                            Ingreso
                                        </span>
                                    @elseif ($tx->transaction_type === 'transfer')
                                        <span class="inline-flex items-center gap-1 text-indigo-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                            </svg>
                                            Transferencia
                                        </span>
                                    @elseif ($tx->transaction_type === 'debt_payment')
                                        <span class="inline-flex items-center gap-1 text-amber-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                            Abono Deuda
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-zinc-300">
                                            <svg class="w-3.5 h-3.5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75" />
                                            </svg>
                                            Gasto
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 font-sans text-zinc-300">
                                    {{ $tx->account?->name ?? 'N/A' }}
                                    @if ($tx->counterAccount)
                                        <span class="text-zinc-500">→</span> {{ $tx->counterAccount->name }}
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 font-sans text-zinc-300">
                                    {{ $tx->budgetItem?->title ?? $tx->notes ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold tabular-nums">
                                    @if ($tx->transaction_type === 'income')
                                        <span class="text-emerald-400">+${{ number_format((float) $tx->amount_original, 2) }}</span>
                                    @elseif ($tx->transaction_type === 'transfer')
                                        <span class="text-indigo-300">${{ number_format((float) $tx->amount_original, 2) }}</span>
                                    @else
                                        <span class="text-white">-${{ number_format((float) $tx->amount_original, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-zinc-500 font-sans">
                                    No hay transferencias o transacciones registradas para este criterio.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create Account Modal --}}
    @if ($showCreateAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-white">Agregar Nueva Cuenta</h3>

                <form wire:submit="createAccount" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Nombre de la Cuenta</label>
                        <x-input wire:model="newAccountName" placeholder="Ej: Banesco USD o Billetera Digital" />
                        @error('newAccountName') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Moneda (ISO)</label>
                            <x-input wire:model="newAccountCurrency" placeholder="USD" maxlength="3" />
                            @error('newAccountCurrency') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Saldo Inicial</label>
                            <x-input wire:model="newAccountBalance" type="number" step="0.01" />
                            @error('newAccountBalance') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <x-button wire:click="$set('showCreateAccountModal', false)" type="button" variant="neutral">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary">
                            Crear Cuenta
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Transfer Modal --}}
    @if ($showTransferModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-white">Transferencia entre Cuentas</h3>

                <form wire:submit="executeTransfer" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuenta de Origen</label>
                        <select wire:model="fromAccountId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm">
                            <option value="">Seleccione cuenta...</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: ${{ number_format((float) $acc->opening_balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('fromAccountId') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Cuenta de Destino</label>
                        <select wire:model="toAccountId" class="w-full bg-zinc-800 border border-zinc-700 text-white rounded-xl px-3 py-2 text-sm">
                            <option value="">Seleccione cuenta...</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: ${{ number_format((float) $acc->opening_balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('toAccountId') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Monto a Transferir ($)</label>
                        <x-input wire:model="transferAmount" type="number" step="0.01" />
                        @error('transferAmount') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Notas / Concepto (Opcional)</label>
                        <x-input wire:model="transferNotes" placeholder="Ej: Retiro de ganancias a cuenta personal" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <x-button wire:click="$toggle('showTransferModal')" type="button" variant="neutral">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary">
                            Ejecutar Transferencia
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit Account Modal --}}
    @if ($showEditAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-white">Editar Cuenta / Método de Pago</h3>

                <form wire:submit="updateAccount" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 mb-1">Nombre de la Cuenta</label>
                        <x-input wire:model="editAccountName" placeholder="Ej: Binance o Banesco USD" />
                        @error('editAccountName') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Moneda (ISO)</label>
                            <x-input wire:model="editAccountCurrency" placeholder="USD" maxlength="3" />
                            @error('editAccountCurrency') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-300 mb-1">Saldo Vivo</label>
                            <x-input wire:model="editAccountBalance" type="number" step="0.01" />
                            @error('editAccountBalance') <span class="text-rose-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <x-button wire:click="$set('showEditAccountModal', false)" type="button" variant="neutral">
                            Cancelar
                        </x-button>
                        <x-button type="submit" variant="primary">
                            Actualizar Cuenta
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Bottom Navigation --}}
    <x-app-bottom-nav />
</div>
