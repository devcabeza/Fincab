<native:scroll-view class="p-4 gap-4">
    <native:column class="gap-1">
        <native:row class="items-center justify-between">
            <native:text class="text-xl font-bold">Registro Rápido</native:text>
            <native:button size="sm" variant="ghost" @tap="backToDashboard">Volver</native:button>
        </native:row>
        <native:text class="text-xs opacity-70">
            Registra un gasto o cuota de deuda al instante
        </native:text>
    </native:column>

    @if ($feedbackMessage !== '')
        <native:column class="p-3 bg-green-50 rounded-lg">
            <native:text class="text-green-700 text-sm font-semibold">{{ $feedbackMessage }}</native:text>
        </native:column>
    @endif

    @if ($errorMessage !== '')
        <native:column class="p-3 bg-red-50 rounded-lg">
            <native:text class="text-red-700 text-sm font-semibold">{{ $errorMessage }}</native:text>
        </native:column>
    @endif

    <native:column class="gap-3">
        <native:outlined-text-input
            label="Descripción del Gasto"
            native:model="title"
            placeholder="Ej. Almuerzo, Mercado, Cuota TC"
        />

        <native:row class="gap-2">
            <native:outlined-text-input
                class="flex-1"
                label="Monto"
                native:model="amount"
                placeholder="0.00"
            />
            <native:outlined-text-input
                class="w-24"
                label="Tasa Cambio"
                native:model="exchangeRate"
            />
        </native:row>

        <native:toggle
            label="¿Es abono a una deuda?"
            native:model="isDebt"
        />

        <native:toggle
            label="¿Es gasto recurrente?"
            native:model="isRecurring"
        />

        <native:row class="gap-2 mt-4">
            <native:button class="flex-1" variant="primary" @tap="saveExpense">
                Guardar Gasto
            </native:button>
            <native:button variant="secondary" @tap="backToDashboard">
                Cancelar
            </native:button>
        </native:row>
    </native:column>
</native:scroll-view>
