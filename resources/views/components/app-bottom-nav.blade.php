@props([
    'active' => null,
])

@php
// Resolve the active tab from the current route unless explicitly provided.
$active = $active ?? match (true) {
    request()->routeIs('dashboard') => 'home',
    request()->routeIs('budget.sheet') => 'budget',
    request()->routeIs('budget.debts') => 'debts',
    request()->routeIs('budget.accounts') => 'accounts',
    request()->routeIs('settings') || request()->routeIs('profile') => 'settings',
    default => null,
};

$itemBase = 'flex flex-col items-center justify-center gap-0.5 min-h-[48px] flex-1 rounded-xl transition-all duration-100 active:scale-95 select-none touch-callout-none';
$itemActive = 'dock-active text-indigo-400 font-semibold';
$itemInactive = 'text-zinc-400 hover:text-zinc-200';
@endphp

{{-- Authenticated mobile bottom navigation (native app pattern, hidden on md+) --}}
<x-bottom-nav class="md:hidden">
    {{-- Inicio / Panel --}}
    <a
        href="{{ route('dashboard') }}"
        wire:navigate
        @if ($active === 'home') aria-current="page" @endif
        class="{{ $itemBase }} {{ $active === 'home' ? $itemActive : $itemInactive }}"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active === 'home' ? '2.2' : '1.8' }}" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
        </svg>
        <span class="dock-label text-[10px] tracking-tight">Panel</span>
    </a>

    {{-- Hoja Presupuesto --}}
    <a
        href="{{ route('budget.sheet') }}"
        wire:navigate
        @if ($active === 'budget') aria-current="page" @endif
        class="{{ $itemBase }} {{ $active === 'budget' ? $itemActive : $itemInactive }}"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active === 'budget' ? '2.2' : '1.8' }}" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
        </svg>
        <span class="dock-label text-[10px] tracking-tight">Partidas</span>
    </a>

    {{-- Deudas --}}
    <a
        href="{{ route('budget.debts') }}"
        wire:navigate
        @if ($active === 'debts') aria-current="page" @endif
        class="{{ $itemBase }} {{ $active === 'debts' ? $itemActive : $itemInactive }}"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active === 'debts' ? '2.2' : '1.8' }}" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
        </svg>
        <span class="dock-label text-[10px] tracking-tight">Deudas</span>
    </a>

    {{-- Cuentas --}}
    <a
        href="{{ route('budget.accounts') }}"
        wire:navigate
        @if ($active === 'accounts') aria-current="page" @endif
        class="{{ $itemBase }} {{ $active === 'accounts' ? $itemActive : $itemInactive }}"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active === 'accounts' ? '2.2' : '1.8' }}" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
        </svg>
        <span class="dock-label text-[10px] tracking-tight">Cuentas</span>
    </a>

    {{-- Ajustes --}}
    <a
        href="{{ route('settings') }}"
        wire:navigate
        @if ($active === 'settings') aria-current="page" @endif
        class="{{ $itemBase }} {{ $active === 'settings' ? $itemActive : $itemInactive }}"
    >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="{{ $active === 'settings' ? '2.2' : '1.8' }}" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="dock-label text-[10px] tracking-tight">Ajustes</span>
    </a>
</x-bottom-nav>
