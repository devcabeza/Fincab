<native:scroll-view class="p-4 gap-4">
    <native:column class="gap-2">
        <native:row class="items-center justify-between">
            <native:text class="text-xl font-bold">{{ $workspace?->name ?? 'Cartera' }}</native:text>
            <native:badge label="Móvil Offline" variant="primary" />
        </native:row>
        <native:text class="text-sm opacity-70">
            Período: {{ $period?->period_start ?? 'Mes en curso' }}
        </native:text>
    </native:column>

    <native:column class="p-4 rounded-xl border border-gray-200 gap-2">
        <native:row class="items-center justify-between">
            <native:text class="font-semibold">Termómetro de Deudas</native:text>
            <native:badge label="${{ $totalDebt }}" variant="destructive" />
        </native:row>
        <native:progress-bar :value="$debtProgress" />
        <native:text class="text-xs opacity-75">
            Saldo pendiente total: ${{ $totalDebt }}
        </native:text>
    </native:column>

    <native:row class="gap-2">
        <native:column class="flex-1 p-3 rounded-lg border border-gray-200 gap-1">
            <native:text class="text-xs opacity-70">Saldo Líquido</native:text>
            <native:text class="text-lg font-bold">${{ $liquidCash }}</native:text>
        </native:column>
        <native:column class="flex-1 p-3 rounded-lg border border-gray-200 gap-1">
            <native:text class="text-xs opacity-70">Comprometido Q1</native:text>
            <native:text class="text-lg font-bold">${{ $committedQ1 }}</native:text>
        </native:column>
    </native:row>

    <native:row class="gap-2">
        <native:button variant="primary" @tap="goToQuickEntry">
            Registrar Gasto Rápido
        </native:button>
        <native:button variant="secondary" @tap="goToDebts">
            Ver Deudas
        </native:button>
    </native:row>

    <native:column class="gap-2 mt-2">
        <native:text class="font-semibold text-md">Partidas Pendientes</native:text>
        @forelse ($pendingItems as $pItem)
            <native:row class="p-3 rounded-lg border border-gray-100 items-center justify-between">
                <native:column class="gap-1">
                    <native:text class="font-medium">{{ $pItem->title }}</native:text>
                    <native:text class="text-xs opacity-70">
                        Q1: ${{ number_format((float) $pItem->amount_q1, 2) }} | Q2: ${{ number_format((float) $pItem->amount_q2, 2) }}
                    </native:text>
                </native:column>
                <native:button size="sm" variant="success" @tap="markAsPaid">
                    Pagar
                </native:button>
            </native:row>
        @empty
            <native:text class="text-sm opacity-60">No hay compromisos pendientes.</native:text>
        @endforelse
    </native:column>
</native:scroll-view>
