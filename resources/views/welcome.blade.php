<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title>Fincab — Control Presupuestario Quincenal y Priorización de Deudas</title>
        <meta name="description" content="Sustituye las hojas de cálculo frágiles por un sistema financiero con cascada presupuestaria en 4 niveles, control de flujo por quincenas, multimoneda y regla estricta Pay Debt First.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Styles & Scripts -->
        <style>
            h1 { font-size: 3rem; }
            h2 { font-size: 2.25rem; }
            h3 { font-size: 1.25rem; }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased font-sans touch-callout-none overflow-x-hidden">
        {{-- Ambient Background Glow --}}
        <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[720px] sm:w-[980px] h-[480px] bg-indigo-600/10 blur-[140px] rounded-full"></div>
            <div class="absolute top-[40%] -left-32 w-[500px] h-[500px] bg-emerald-600/8 blur-[130px] rounded-full"></div>
            <div class="absolute bottom-10 right-0 w-[450px] h-[450px] bg-indigo-500/8 blur-[120px] rounded-full"></div>
        </div>

        <div class="min-h-screen-safe w-full max-w-full overflow-x-hidden flex flex-col justify-between pb-safe">
            {{-- Top Sticky Header --}}
            <header class="w-full sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/60 transition-colors pt-safe">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                    {{-- Brand Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 select-none group shrink-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-zinc-900 border border-zinc-800 p-2 flex items-center justify-center group-hover:border-indigo-500/50 transition-colors shadow-sm">
                            <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-full h-full object-contain pointer-events-none">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-lg sm:text-xl tracking-tight text-white group-hover:text-indigo-300 transition-colors">Fincab</span>
                            <span class="text-xs text-zinc-400 font-medium px-2 py-0.5 rounded-md border border-zinc-800 hidden sm:inline-flex">
                                Finanzas Pro
                            </span>
                        </div>
                    </a>

                    {{-- Navigation Links (Desktop) --}}
                    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-zinc-400 select-none">
                        <a href="#metodo" class="hover:text-white transition-colors">Método Cascada</a>
                        <a href="#quincenas" class="hover:text-white transition-colors">Flujo Quincenal</a>
                        <a href="#deudas" class="hover:text-white transition-colors">Estrategias de Deuda</a>
                        <a href="#multimoneda" class="hover:text-white transition-colors">Multimoneda</a>
                        <a href="#faq" class="hover:text-white transition-colors">Preguntas Frecuentes</a>
                    </nav>

                    {{-- Header CTAs --}}
                    <div class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <x-button href="{{ route('dashboard') }}" variant="primary" size="sm" class="shadow-md shadow-indigo-600/20 min-h-[44px] px-3.5 sm:px-4 text-xs sm:text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25-2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                <span>Ir al Panel</span>
                            </x-button>
                        @else
                            <x-button href="{{ route('login') }}" variant="primary" size="sm" class="min-h-[44px] px-4 text-xs sm:text-sm shadow-md shadow-indigo-600/20">
                                <span>Iniciar Sesión</span>
                            </x-button>
                        @endauth
                    </div>
                </div>
            </header>

            {{-- Main Content Container --}}
            <main class="flex-1 flex flex-col w-full max-w-full">
                {{-- Hero Section --}}
                <section class="relative pt-16 sm:pt-24 pb-16 sm:pb-24 overflow-hidden border-b border-zinc-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="text-center max-w-3xl mx-auto space-y-6">
                            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                                Control presupuestario quincenal y liquidación de deudas
                            </h1>

                            <p class="text-base sm:text-lg text-zinc-300 leading-relaxed max-w-2xl mx-auto">
                                Deja atrás las hojas de cálculo con fórmulas que se rompen. Fincab aplica la regla estricta <strong class="text-white font-semibold">Pay Debt First</strong>, sincroniza tasas reales y divide tu flujo entre Q1 y Q2 para blindar tu liquidez.
                            </p>

                            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                                <x-button href="{{ route('login') }}" variant="primary" size="lg" class="w-full sm:w-auto min-h-[48px] px-8 text-base shadow-lg shadow-indigo-600/25">
                                    <span>Comenzar Ahora con Magic Link</span>
                                </x-button>
                                <x-button href="#metodo" variant="secondary" size="lg" class="w-full sm:w-auto min-h-[48px] px-6 text-base">
                                    <span>Conocer el Método</span>
                                </x-button>
                            </div>

                            {{-- Proof badges --}}
                            <div class="pt-6 flex flex-wrap items-center justify-center gap-6 text-xs text-zinc-400">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Cero fórmulas rotas de Excel</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Cuotas mínimas blindadas</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Soporte dinámico USD, COP y VES</span>
                                </div>
                            </div>
                        </div>

                        {{-- Interactive Dashboard Mockup Preview --}}
                        <div class="mt-12 sm:mt-16 max-w-5xl mx-auto rounded-2xl sm:rounded-3xl bg-zinc-900 border border-zinc-800 p-4 sm:p-6 shadow-2xl shadow-indigo-950/40">
                            {{-- Mockup Window Chrome --}}
                            <div class="flex items-center justify-between pb-4 border-b border-zinc-800 text-xs text-zinc-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                    <span class="ml-2 font-mono text-zinc-400">fincab.app/dashboard</span>
                                </div>
                                <div class="flex items-center gap-3 font-semibold text-white">
                                    <span class="px-2.5 py-1 rounded-lg bg-zinc-800 border border-zinc-700 font-mono text-xs">Personal (USD)</span>
                                    <span class="px-2.5 py-1 rounded-lg bg-zinc-800 border border-zinc-700 font-mono text-xs">Noviembre 2026</span>
                                </div>
                            </div>

                            {{-- Mockup Core Content --}}
                            <div class="pt-6 space-y-6">
                                {{-- Termómetro de Deuda Preview --}}
                                <div class="p-4 sm:p-5 rounded-xl bg-zinc-950/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-white text-base">Termómetro de Deudas</span>
                                            <span class="px-2 py-0.5 rounded text-[11px] bg-indigo-500/20 text-indigo-300 font-semibold">Prioridad #1</span>
                                        </div>
                                        <p class="text-xs text-zinc-400">Saldo vivo: <strong class="text-white font-mono tabular-nums">$3,450.00</strong> | Libertad proyectada: <strong class="text-emerald-400">Septiembre 2027</strong></p>
                                    </div>
                                    <div class="w-full sm:w-64 space-y-1.5">
                                        <div class="flex justify-between text-xs font-semibold">
                                            <span class="text-zinc-400">Amortizado</span>
                                            <span class="text-emerald-400 font-mono tabular-nums">62%</span>
                                        </div>
                                        <div class="w-full h-2 rounded-full bg-zinc-800 overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full w-[62%]"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- KPIs Grid --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 font-mono tabular-nums">
                                    <div class="p-3.5 rounded-xl bg-zinc-950/40">
                                        <span class="text-xs text-zinc-400 font-sans">Ingreso Confirmado</span>
                                        <div class="text-lg font-bold text-white mt-1">$3,000.00</div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-zinc-950/40">
                                        <span class="text-xs text-zinc-400 font-sans">Comprometido Q1 (1-15)</span>
                                        <div class="text-lg font-bold text-amber-300 mt-1">$1,180.00</div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-zinc-950/40">
                                        <span class="text-xs text-zinc-400 font-sans">Comprometido Q2 (16-30)</span>
                                        <div class="text-lg font-bold text-indigo-300 mt-1">$1,120.00</div>
                                    </div>
                                    <div class="p-3.5 rounded-xl bg-zinc-950/40">
                                        <span class="text-xs text-zinc-400 font-sans">Ahorro Real Proyectado</span>
                                        <div class="text-lg font-bold text-emerald-400 mt-1">$700.00</div>
                                    </div>
                                </div>

                                {{-- Waterfall Bars Preview --}}
                                <div class="space-y-3 pt-2">
                                    <div class="flex items-center justify-between text-xs font-semibold text-zinc-300">
                                        <span>Cascada de Asignación Presupuestaria</span>
                                        <span class="text-zinc-400 font-mono tabular-nums">100% Asignado</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs">
                                        <div class="p-2.5 rounded-lg bg-zinc-950/40">
                                            <div class="flex justify-between font-semibold text-emerald-300">
                                                <span>1. Esenciales</span>
                                                <span class="font-mono tabular-nums">50%</span>
                                            </div>
                                            <div class="text-zinc-400 mt-0.5 font-mono tabular-nums">$1,500.00</div>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-zinc-950/40">
                                            <div class="flex justify-between font-semibold text-rose-300">
                                                <span>2. Mínimos Deuda</span>
                                                <span class="font-mono tabular-nums">25%</span>
                                            </div>
                                            <div class="text-zinc-400 mt-0.5 font-mono tabular-nums">$750.00 (Blindado)</div>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-zinc-950/40">
                                            <div class="flex justify-between font-semibold text-indigo-300">
                                                <span>3. Acelerador Avalancha</span>
                                                <span class="font-mono tabular-nums">15%</span>
                                            </div>
                                            <div class="text-zinc-400 mt-0.5 font-mono tabular-nums">$450.00 (Abono extra)</div>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-zinc-950/40">
                                            <div class="flex justify-between font-semibold text-amber-300">
                                                <span>4. Ocio / Inversión</span>
                                                <span class="font-mono tabular-nums">10%</span>
                                            </div>
                                            <div class="text-zinc-400 mt-0.5 font-mono tabular-nums">$300.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Feature 1: The Waterfall Engine --}}
                <section id="metodo" class="py-16 sm:py-24 border-b border-zinc-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                        <div class="text-center max-w-2xl mx-auto space-y-3">
                            <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                                Motor de Cascada "Pay Debt First"
                            </h2>
                            <p class="text-zinc-400 text-sm sm:text-base">
                                La disciplina financiera codificada en reglas automáticas inmutables.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold font-mono">1</div>
                                <h3 class="text-base font-bold text-white">Gastos Esenciales</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Cubre primero los costos indispensables de supervivencia y operación básica (vivienda, servicios, alimentación).
                                </p>
                            </x-card>

                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 font-bold font-mono">2</div>
                                <h3 class="text-base font-bold text-white">Cuotas Mínimas de Deuda</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Protección obligatoria. Impide que caigas en mora o intereses punitivos garantizando las cuotas mínimas antes de cualquier gasto discrecional.
                                </p>
                            </x-card>

                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold font-mono">3</div>
                                <h3 class="text-base font-bold text-white">Acelerador de Deuda</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Canaliza todo excedente de flujo hacia la deuda focal prioritaria según el método Avalancha (mayor interés) o Bola de Nieve (menor saldo).
                                </p>
                            </x-card>

                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold font-mono">4</div>
                                <h3 class="text-base font-bold text-white">Ocio e Inversión Libre</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Solo accesible cuando los tres niveles anteriores están blindados y solventes.
                                </p>
                            </x-card>
                        </div>
                    </div>
                </section>

                {{-- Feature 2: Quincenas and Debt Lifecycle --}}
                <section id="quincenas" class="py-16 sm:py-24 border-b border-zinc-900 bg-zinc-950/50">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                            <div class="space-y-6">
                                <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                                    Cálculo bidireccional exacto y salvaguardas de ocio
                                </h2>
                                <p class="text-zinc-300 text-sm sm:text-base leading-relaxed">
                                    El problema común de las hojas de Excel es quedarse sin dinero a mitad de mes. Fincab modela cada partida con montos independientes para la <strong>Q1 (días 1–15)</strong> y la <strong>Q2 (días 16–30)</strong>.
                                </p>
                                <div class="space-y-4 pt-2">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-white">Clonación Limpia de Mes</h3>
                                            <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">Copia exclusivamente partidas recurrentes (alquileres, suscripciones). Limpia a $0.00 los gastos variables y reinicia estados a Pendiente sin arrastre de saldos residuales.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-white">Bloqueo Automático de Ocio</h3>
                                            <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">Si el calendario supera el día 15 y existen partidas de deuda de la Q1 sin pagar, el sistema restringe nuevos gastos innecesarios hasta ponerte al día.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-white">Amortización Reactiva Inmediata</h3>
                                            <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">Al marcar un compromiso como Pagado, un Observer atómico descuenta el saldo de la deuda y registra el movimiento contable sin recargar la página.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Visual Comparison Box --}}
                            <div class="p-6 sm:p-8 rounded-3xl bg-zinc-900 border border-zinc-800 space-y-6">
                                <h3 class="text-lg font-bold text-white border-b border-zinc-800 pb-3">Estrategias de Priorización de Pasivos</h3>
                                <div class="space-y-4 divide-y divide-zinc-800/80">
                                    <div class="space-y-2 pt-2 first:pt-0">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-white text-sm flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                                </svg>
                                                Método Avalancha
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-semibold">Ahorro Máximo</span>
                                        </div>
                                        <p class="text-xs text-zinc-400 leading-relaxed">Ordena tus deudas de mayor a menor tasa de interés. Minimiza los intereses totales pagados a largo plazo.</p>
                                    </div>
                                    <div class="space-y-2 pt-4">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-white text-sm flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Método Bola de Nieve
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">Impulso Rápido</span>
                                        </div>
                                        <p class="text-xs text-zinc-400 leading-relaxed">Ordena de menor a mayor saldo pendiente. Elimina cuentas rápido para generar victorias psicológicas tempranas.</p>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-zinc-800 text-xs text-zinc-300 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.002 6.002 0 00-4-5.659V5.25a2 2 0 014 0v1.841c1.558.455 2.766 1.706 3.197 3.268m-7.197 2.141a6 6 0 004 5.659" />
                                    </svg>
                                    <span><strong class="text-white">Regla de Ingresos Extraordinarios:</strong> Al recibir un ingreso variable, Fincab te asiste para destinar entre el 50% y 70% directamente a la deuda prioritaria.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Feature 3: Multicurrency and Native Mobile --}}
                <section id="multimoneda" class="py-16 sm:py-24 border-b border-zinc-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                        <div class="text-center max-w-2xl mx-auto space-y-3">
                            <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                                Multimoneda Integrada y Operación Ágil
                            </h2>
                            <p class="text-zinc-400 text-sm sm:text-base">
                                Diseñado para la realidad económica de América Latina y nómadas digitales.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-white">Tasas Diarias en Vivo</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Consulta tasas oficiales BCV, Paralelo, Binance P2P y TRM Colombia con actualización silenciosa y conversión matemática a USD sin fricción.
                                </p>
                            </x-card>

                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-white">Móvil con Controles Nativos</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Arquitectura nativa con sincronización segura. Tu información financiera disponible tanto en tu ordenador como en la palma de tu mano.
                                </p>
                            </x-card>

                            <x-card class="space-y-3 border-zinc-800 bg-zinc-900/60">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-7.5 4.5l3-3m0 0l3 3m-3-3v8.25M19.5 12a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-white">Operación Offline Confiable</h3>
                                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                    Registra gastos en la calle sin depender de cobertura. Los datos se consolidan localmente y se replican al restablecer conexión.
                                </p>
                            </x-card>
                        </div>
                    </div>
                </section>

                {{-- FAQ Section --}}
                <section id="faq" class="py-16 sm:py-24 border-b border-zinc-900">
                    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                        <div class="text-center space-y-3">
                            <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                                Preguntas Frecuentes
                            </h2>
                            <p class="text-zinc-400 text-sm sm:text-base">
                                Todo lo que necesitas saber para ordenar tu dinero con Fincab.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <x-collapse title="¿Por qué es mejor que llevar mis finanzas en un Excel?" :open="true">
                                Las hojas de cálculo tienen tres fallas graves: fórmulas que se rompen al insertar filas, arrastre accidental de gastos pasados al clonar meses y ausencia de reglas que impidan gastar dinero comprometido. Fincab garantiza cálculos inmutables, cuotas mínimas protegidas y clonación limpia en 1 clic.
                            </x-collapse>

                            <x-collapse title="¿Cómo funciona el acceso sin contraseñas (Magic Link)?">
                                Solo ingresas tu correo electrónico y recibes un código de 6 dígitos y un enlace de un solo uso. No necesitas memorizar contraseñas ni correr riesgos de filtraciones de claves.
                            </x-collapse>

                            <x-collapse title="¿Puedo separar mis finanzas personales de mi negocio?">
                                Sí. Fincab soporta múltiples Carteras (Workspaces) independientes. Puedes tener tu cartera personal y la de tu emprendimiento totalmente separadas, con sus propias cuentas, presupuestos y deudas.
                            </x-collapse>

                            <x-collapse title="¿Cómo maneja la app los gastos en moneda extranjera?">
                                Cada gasto almacena la moneda original (ej. Pesos Colombianos o Bolívares), la tasa de cambio vigente y su equivalente en dólares. Esto te permite auditar consumos en moneda local sin distorsionar el balance global en tu moneda base.
                            </x-collapse>

                            <x-collapse title="¿Qué sucede si no tengo internet al registrar un gasto en el móvil?">
                                La aplicación móvil guarda la mutación en su base de datos local SQLite. En cuanto el dispositivo recupera la conexión, el gestor de sincronización envía las partidas pendientes a la base de datos central sin pérdida de información.
                            </x-collapse>
                        </div>
                    </div>
                </section>

                {{-- Final CTA Section --}}
                <section class="py-16 sm:py-24 bg-gradient-to-b from-zinc-950 via-indigo-950/20 to-zinc-950">
                    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                        <h2 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">
                            Comienza a liquidar tus deudas hoy mismo
                        </h2>
                        <p class="text-zinc-300 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                            Únete a Fincab y experimenta la claridad de un presupuesto estructurado por quincenas sin estrés financiero.
                        </p>
                        <div class="pt-4">
                            <x-button href="{{ route('login') }}" variant="primary" size="lg" class="min-h-[50px] px-10 text-base shadow-xl shadow-indigo-600/30">
                                <span>Iniciar Sesión con Magic Link</span>
                            </x-button>
                        </div>
                    </div>
                </section>
            </main>

            {{-- Footer --}}
            <footer class="w-full border-t border-zinc-900 bg-zinc-950 py-10 text-xs text-zinc-500">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('favicon.svg') }}" alt="Fincab" class="w-6 h-6">
                        <span class="font-bold text-sm text-white">Fincab</span>
                        <span>— Sistema de Control Presupuestario Quincenal y Priorización de Deudas</span>
                    </div>
                    <div>
                        © {{ date('Y') }} Fincab. Todos los derechos reservados.
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
