<native:scroll-view class="p-4 gap-4">
    <native:column class="gap-1">
        <native:row class="items-center justify-between">
            <native:text class="text-xl font-bold">Gestor de Deudas</native:text>
            <native:button size="sm" variant="ghost" @tap="backToDashboard">Volver</native:button>
        </native:row>
        <native:text class="text-xs opacity-70">
            Priorización inteligente con regla Pay Debt First
        </native:text>
    </native:column>

    @if ($statusMessage !== '')
        <native:column class="p-3 bg-blue-50 rounded-lg">
            <native:text class="text-blue-700 text-sm font-semibold">{{ $statusMessage }}</native:text>
        </native:column>
    @endif

    <native:column class="p-3 rounded-xl border border-gray-200 gap-2">
        <native:text class="font-semibold text-sm">Estrategia de Amortización</native:text>
        <native:row class="gap-2">
            <native:button
                class="flex-1"
                :variant="$activeStrategy === 'avalanche' ? 'primary' : 'outline'"
                @tap="setAvalanche"
            >
                Avalancha (Interés)
            </native:button>
            <native:button
                class="flex-1"
                :variant="$activeStrategy === 'snowball' ? 'primary' : 'outline'"
                @tap="setSnowball"
            >
                Bola de Nieve (Saldo)
            </native:button>
        </native:row>
    </native:column>

    <native:column class="gap-3 mt-2">
        <native:text class="font-semibold text-md">Deudas Activas (Orden Prioritario)</native:text>
        @forelse ($debts as $index => $debt)
            @php
                $initial = (float) $debt->initial_balance;
                $current = (float) $debt->current_balance;
                $pct = $initial > 0 ? max(0.0, min(1.0, ($initial - $current) / $initial)) : 0.0;
            @endphp
            <native:column class="p-4 rounded-xl border border-gray-200 gap-2">
                <native:row class="items-center justify-between">
                    <native:text class="font-bold">#{{ $index + 1 }} {{ $debt->name }}</native:text>
                    <native:badge :label="'Tasa: ' . $debt->annual_interest_rate . '%'" variant="accent" />
                </native:row>
                <native:row class="items-center justify-between">
                    <native:text class="text-xs opacity-70">Saldo restante:</native:text>
                    <native:text class="font-semibold text-destructive">${{ number_format($current, 2) }}</native:text>
                </native:row>
                <native:progress-bar :value="$pct" />
                <native:text class="text-xs opacity-60">
                    Cuota mínima requerida: ${{ number_format((float) $debt->minimum_payment, 2) }}
                </native:text>
            </native:column>
        @empty
            <native:text class="text-sm opacity-60">¡Felicidades! No tienes deudas registradas.</native:text>
        @endforelse
    </native:column>
</native:scroll-view>
