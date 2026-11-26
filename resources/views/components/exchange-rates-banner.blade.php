@if ($bcv || $paralelo || $binanceVes || $cop)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2 sm:gap-3 text-xs font-mono py-1 px-2.5 rounded-xl bg-zinc-950/80 border border-zinc-800/80 shadow-inner overflow-x-auto no-scrollbar select-none']) }}>
        <div class="flex items-center gap-1.5 shrink-0 text-zinc-400 font-sans text-[11px] font-semibold pr-1 border-r border-zinc-800">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Hoy:</span>
        </div>

        @if ($bcv)
            <div class="flex items-center gap-1 shrink-0" title="Banco Central de Venezuela (Oficial)">
                <span class="text-zinc-400 font-sans text-[11px]">BCV</span>
                <span class="font-bold text-white">{{ number_format((float) $bcv, 2) }}</span>
            </div>
        @endif

        @if ($paralelo)
            <span class="text-zinc-700 shrink-0">•</span>
            <div class="flex items-center gap-1 shrink-0" title="Promedio Mercado Paralelo">
                <span class="text-amber-400/90 font-sans text-[11px]">Paralelo</span>
                <span class="font-bold text-amber-300">{{ number_format((float) $paralelo, 2) }}</span>
            </div>
        @endif

        @if ($binanceVes)
            <span class="text-zinc-700 shrink-0">•</span>
            <div class="flex items-center gap-1 shrink-0" title="Binance P2P USDT/VES">
                <span class="text-yellow-400/90 font-sans text-[11px]">Binance</span>
                <span class="font-bold text-yellow-300">{{ number_format((float) $binanceVes, 2) }}</span>
            </div>
        @endif

        @if ($cop)
            <span class="text-zinc-700 shrink-0">•</span>
            <div class="flex items-center gap-1 shrink-0" title="Pesos Colombianos (TRM Oficial / P2P)">
                <span class="text-emerald-400/90 font-sans text-[11px]">COP</span>
                <span class="font-bold text-emerald-300">{{ number_format((float) $cop, 0) }}</span>
            </div>
        @endif
    </div>
@endif
