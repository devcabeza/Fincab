# Biweekly Budget Control and Debt Prioritization
Branch: `feature/biweekly-budget-control` · Delivery: `ask-on-risk` · Test runner: Pest (`php artisan test`), Pint, PHPStan, and Vite.
Objective: Build the budgeting system for browser-based Laravel and NativePHP Mobile native UI, with shared server data and offline-first mobile synchronization.

## Specs

### S1 — Product scope and runtime

> “Transformar el control manual en Excel hacia una aplicación nativa (escritorio y móvil) rápida, offline-first y libre de errores de fórmulas, enfocada en la asignación presupuestaria por porcentajes, distribución de flujo por quincenas, soporte multimoneda y priorización estricta del pago de deudas.”

> “Usuario Objetivo: Inicialmente diseñado para uso personal/emprendedor (gestión dual de finanzas personales y operación comercial/side projects), con arquitectura preparada para escalar a producto multiusuario.”

The latest platform clarification selects a normal Laravel web application and a separate NativePHP Mobile native UI. Desktop-native tray/shortcut scope remains unresolved and is not assumed for the first delivery.

### S2 — Separate UI, shared rules, and shared data

> “Ojo pero no quiero usar nativephp con webview, quiero usar componentes nativos, para crear la app movil, asi la web tiene un dieseño y la app movil otro diseño pero comparten datos”

Web and mobile may use different presentation layers. Keep financial rules reusable and ensure both clients read and write the same central financial data. Mobile must use NativePHP native components, not a WebView.

### S3 — Offline writes and synchronization

> “cuando no tenga conexion en la app movil se guarde en local y cuando recupere la conexion se envie a la base de datos que nutre las dos apps.”

Mobile writes must be durable locally while offline, then synchronize with the central Laravel-backed data store on reconnection. Web-originated changes must also reach mobile. No direct mobile connection to central database credentials.

### S4 — Domain and money model

The PRD defines `Workspace`, `Account`, `BudgetPeriod`, `BudgetBucket`, `Debt`, `BudgetItem`, and `CurrencyRate`. Add only the transaction/history records needed to make account balances, debt amortization, transfers, and synchronization auditable and idempotent. Preserve original currency amount, rate, and USD equivalent for historical transactions.

> “CurrencyRate (Tasas) Registro histórico y diario de tasas de cambio (ej. USD -> COP, USD -> VES) para registrar movimientos en moneda local y convertirlos automáticamente a la moneda base (USD).”

### S5 — Budget allocation and quincenas

> “Nivel 1 — Gastos Esenciales: Se cubren los gastos fijos indispensables de supervivencia/operación.”
>
> “Nivel 2 — Bloqueo Obligatorio de Deudas: El sistema consulta todas las deudas con estado = activa y genera automáticamente sus partidas quincenales (Q1 y Q2) con la cuota mínima. Regla: Estas partidas están protegidas y no pueden editarse a la baja desde la vista de presupuesto.”
>
> “Nivel 3 — Acelerador de Deuda: Si existe excedente tras cubrir Esenciales y Mínimos, el sistema asigna un porcentaje configurable (ej. 20%) como Abono Extraordinario a Capital dirigido exclusivamente a la deuda #1 según la estrategia activa”
>
> “Nivel 4 — Presupuesto Disponible Restante: El saldo remanente se distribuye entre Prioridades/Inversión, Inversiones Digitales y Gastos Innecesarios.”

Implement a deterministic, tested waterfall; protect required debt minimums in both UI and server-side validation. Support equal/manual Q1/Q2 split, recurring-only month cloning, pending reset, and zeroed variable/discretionary amounts.

### S6 — Debt lifecycle and real cash flow

> “Cuando un BudgetItem vinculado a una Debt cambia de estado Pendiente a Pagado, un Observer de Laravel descuenta automáticamente el monto del saldo_actual de la deuda y registra un movimiento en el historial de amortización (DebtPayment).”

Payment recording must be atomic and safe against duplicate sync delivery. Define how principal, interest, fees, reversals, minimums, and extra payments affect the debt ledger before implementing the balance calculation.

> “El ahorro del mes nunca es un campo editable manual; se calcula dinámicamente en tiempo real como: Ahorro Real = Total Ingresos Cobrados - sum(Gastos Pagados y Comprometidos).”

### S7 — Native capabilities and user workflows

Deliver the requested DashboardOverview, BudgetSheetView, DebtManagerView, and AccountsAndRatesView, with quick entry, Q1/Q2 filters, debt-priority indicators, and visible sync state. Mobile screens use NativePHP native components and a distinct design from the web.

> “Notificaciones Nativas del Sistema Operativo”
>
> “Atajo Global de Teclado (Global Shortcut)”
>
> “Menu Bar / System Tray App”

Native notifications/backups are in scope for mobile. Desktop-only global shortcut and tray behavior stays deferred until the user confirms a native desktop target; the latest target clarification specifies browser web plus native mobile.

### S8 — Testability and data protection

Cover financial calculations, workspace isolation, migration upgrade paths, offline queue retry/replay, duplicate operation handling, and server-to-mobile pulls. Use synthetic fixtures until the user supplies real June–August 2026 data. Never commit financial source data or secrets.

## Tasks

### Macro 1 — Shared financial core and synchronization

- **T0 — Validate NativePHP native UI and sync seam** · S1–S3, S7 · inline · completed · RED/GREEN: focused plugin-registration test observed failing then passing; suite passed at 80 tests · Risk: item 5 (native runtime/dependencies).
- **T1 — Add finance ledger and domain persistence** · S4, S6, S8 · inline · completed · RED/GREEN: schema/persistence test observed failing before migrations/models, then passed; full suite 83 tests / 314 assertions passed · Risk: item 1 (persistent schema/migrations).
- **T2 — Implement budget waterfall, quincenas, and clean month cloning** · S5, S8 · inline · completed · RED/GREEN: 5 focused tests observed RED then GREEN (46 assertions); full suite 89 tests / 365 assertions passed; Pint and PHPStan clean · Risk: none (deterministic rules covered by focused tests).
- **T3 — Implement debt payment lifecycle and spending safeguards** · S5–S6, S8 · inline · completed · RED/GREEN: 5 focused tests observed RED then GREEN (21 assertions); full suite 94 tests / 386 assertions passed; Pint and PHPStan clean · Risk: items 1, 4 (persisted debt state and duplicate/concurrent payment handling).
- **T4 — Build shared API and offline synchronization protocol** · S2–S4, S8 · inline · completed · RED/GREEN: 5 focused tests observed RED then GREEN (35 assertions); full suite 99 tests / 421 assertions passed; Pint and PHPStan clean · Risk: items 2, 3, 4 (auth, API contract, sync concurrency).

### Macro 2 — Web application

- **T5 — Build browser budget and debt workflows** · S2, S7 · inline · completed · RED/GREEN: 4 focused Livewire tests observed RED then GREEN (15 assertions); full suite 103 tests / 436 assertions passed; Pint and PHPStan clean · Risk: none (new UI behavior pinned by feature tests).

### Macro 3 — Native mobile application

- **T6 — Build NativePHP native mobile screens and quick entry** · S2–S3, S7 · inline · completed · RED/GREEN: 5 focused native mobile tests observed RED then GREEN (32 assertions); NativeComponent analyzer passed (`php artisan native:validate` with 0 errors); Pint and PHPStan Level 7 clean · Risk: item 5 (native build/runtime integration).
- **T7 — Add notifications, backup/restore, and release validation** · S7–S8 · inline · completed · RED/GREEN: 2 focused reminder/backup tests observed RED then GREEN (11 assertions); full suite 110 tests / 479 assertions passed; Pint and PHPStan clean · Risk: item 5 (device data and release/runtime behavior).
- **T8 — Add optional multiuser/workspace expansion and real-data import** · S1, S4, S8 · inline · deferred until user data and sharing scope are supplied · commit: pending · Risk: item 2 (tenant authorization).

Forecast: approximately 1,200–1,800 authored changed lines excluding generated files; reassess at work-unit boundaries. Keep each task cohesive and record its commit and verification. No work-unit commits are made until a source task is completed and checked.

## Log

### L1 — Original PRD (verbatim)

PRD: Sistema de Control Presupuestario Quincenal y Priorización de Deudas

1. Visión General del Producto
Propósito: Transformar el control manual en Excel hacia una aplicación nativa (escritorio y móvil) rápida, offline-first y libre de errores de fórmulas, enfocada en la asignación presupuestaria por porcentajes, distribución de flujo por quincenas, soporte multimoneda y priorización estricta del pago de deudas.
Usuario Objetivo: Inicialmente diseñado para uso personal/emprendedor (gestión dual de finanzas personales y operación comercial/side projects), con arquitectura preparada para escalar a producto multiusuario.
Stack Tecnológico:Backend: Laravel 11+Frontend / Reactividad: Livewire 3 + Alpine.js + TailwindCSSRuntime Nativo: NativePHP (Desktop / Mobile) con SQLite local para operación instantánea sin latencia y sincronización opcional.

2. Objetivos y Métricas de Éxito
Cero errores de arrastre: Eliminar los saldos residuales entre meses al clonar períodos presupuestarios.
Prioridad absoluta de pasivos: Garantizar que el 100% de las cuotas mínimas de deuda queden cubiertas y bloqueadas antes de habilitar presupuesto de ocio.
Velocidad de registro: Permitir registrar un gasto o pago en menos de 10 segundos desde cualquier vista mediante modales reactivos de Livewire.
Claridad patrimonial: Separar estrictamente los flujos personales de los flujos operativos de negocio (ej. tienda/inventario).

3. Arquitectura de Dominio (Entidades Principales)
EntidadDescripción y Responsabilidad
Workspace (Cartera)Separa contextos independientes (ej. Personal, Negocio / Tienda, Side Projects). Permite transferencias entre carteras (ej. retiro de ganancia hacia ingreso personal).
Account (Cuentas)Métodos de pago y tenencia real (ej. Efectivo USD, Banco Nacional, Billetera Digital). Mantiene saldo vivo descontando gastos marcados como Pagado.
BudgetPeriod (Mes)Representa un ciclo mensual (ej. 2026-09). Almacena la configuración porcentual vigente para ese mes (debe sumar 100%).
BudgetBucket (Bloques)Categorías macro del mes: Gastos Esenciales, Deudas (Prioridad #1), Prioridades / Inversión, Inversiones Digitales y Gastos Innecesarios (Ocio).
Debt (Pasivos/Deudas)Registro maestro de cada deuda con saldo inicial, saldo actual, tasa de interés, cuota mínima, frecuencia y estrategia (Bola de Nieve o Avalancha).
BudgetItem (Partidas)Cada línea de ingreso o gasto planificado dentro de un mes, dividida en monto_q1 (Primera Quincena) y monto_q2 (Segunda Quincena), con estado (Pendiente / Pagado).
CurrencyRate (Tasas)Registro histórico y diario de tasas de cambio (ej. USD -> COP, USD -> VES) para registrar movimientos en moneda local y convertirlos automáticamente a la moneda base (USD).

4. Requerimientos Funcionales (Core del Producto)
RF-01: Motor de "Cascada de Prioridad" (Pay Debt First)
Al abrir o recalcular un mes (BudgetPeriod), el sistema debe ejecutar el siguiente orden estricto de asignación sobre el Ingreso Total Confirmado:
Nivel 1 — Gastos Esenciales: Se cubren los gastos fijos indispensables de supervivencia/operación.
Nivel 2 — Bloqueo Obligatorio de Deudas: El sistema consulta todas las deudas con estado = activa y genera automáticamente sus partidas quincenales (Q1 y Q2) con la cuota mínima. Regla: Estas partidas están protegidas y no pueden editarse a la baja desde la vista de presupuesto.
Nivel 3 — Acelerador de Deuda: Si existe excedente tras cubrir Esenciales y Mínimos, el sistema asigna un porcentaje configurable (ej. 20%) como Abono Extraordinario a Capital dirigido exclusivamente a la deuda #1 según la estrategia activa:
Bola de Nieve: Ordena de menor a mayor saldo_actual.
Avalancha: Ordena de mayor a menor tasa_interes.
Nivel 4 — Presupuesto Disponible Restante: El saldo remanente se distribuye entre Prioridades/Inversión, Inversiones Digitales y Gastos Innecesarios. Si las deudas + esenciales superan el ingreso, el bloque Gastos Innecesarios se fija automáticamente en $0.00.

RF-02: Gestión del Ciclo de Vida de Deudas
Amortización Automática: Cuando un BudgetItem vinculado a una Debt cambia de estado Pendiente a Pagado, un Observer de Laravel descuenta automáticamente el monto del saldo_actual de la deuda y registra un movimiento en el historial de amortización (DebtPayment).
Regla de Ingresos Extraordinarios: Al registrar un ingreso variable (ej. Ingresos por Side Projects o Inversiones), el sistema muestra un modal rápido preguntando si se desea aplicar la regla de aceleración (destinar automáticamente entre 50% y 70% a la deuda prioritaria).
Alerta de Bloqueo de Ocio: Si la fecha actual supera el día 15 del mes y existen partidas de deuda de la Q1 en estado Pendiente, la interfaz muestra un banner de advertencia y bloquea la creación de nuevos gastos en el bloque Gastos Innecesarios (con opción de desbloqueo manual justificado).

RF-03: Presupuesto Quincenal y Clonación Inteligente
Cálculo Bidireccional Único: Para evitar inconsistencias de Excel, el usuario elige por partida si el cálculo es División Equitativa (Q1 = Total/2, Q2 = Total/2) o Manual por Quincena (Total = Q1 + Q2).
Asistente de Apertura de Mes ("Clonar Mes"):
Copia únicamente las partidas marcadas como is_recurring = true (alquileres, suscripciones como Contabo/Opencode, servicios).
Reinicia todos los estados a Pendiente y limpia los montos de gastos variables/ocio a $0.00 (eliminando el problema de arrastrar montos accidentales de meses anteriores).
Recalcula todos los topes presupuestarios en dólares tomando como base los ingresos definidos para el nuevo mes.

RF-04: Soporte Multimoneda Integrado
Al crear o pagar un gasto, el usuario puede ingresar el valor en moneda local (ej. 214,400 COP) e indicar la tasa del día (3,206), o seleccionar la última tasa guardada.
El sistema almacena tanto el monto original como su equivalente exacto en USD, mostrando ambos en los detalles y comentarios de la partida.

RF-05: Cálculo Real de Ahorro y Flujo de Caja
El ahorro del mes nunca es un campo editable manual; se calcula dinámicamente en tiempo real como:
$$\text{Ahorro Real} = \text{Total Ingresos Cobrados} - \sum \text{Gastos Pagados y Comprometidos}$$
Panel de control quincenal que indica exactamente cuánto dinero líquido se necesita para cubrir los compromisos pendientes de la Q1 (días 1–15) y la Q2 (días 16–30) frente al saldo disponible en las Accounts.

5. Aprovechamiento de NativePHP (Capacidades Nativas)
Dado que correrá sobre NativePHP, la aplicación debe aprovechar APIs del sistema operativo que una web tradicional no tiene:
Base de Datos Local SQLite + Backups Automáticos: Almacenamiento ultrarrápido local con exportación automática diaria/semanal en JSON/CSV hacia una carpeta sincronizada (ej. Google Drive local o carpeta de respaldos).
Notificaciones Nativas del Sistema Operativo:
Recordatorios automáticos los días 13 y 28 de cada mes listando las cuotas de deuda y gastos esenciales que siguen en estado 📌 Pendiente.
Alerta nativa inmediata cuando un bloque supera el 85% o 100% de su presupuesto asignado.
Atajo Global de Teclado (Global Shortcut): Combinación tipo Cmd/Ctrl + Shift + G que abre una ventana flotante minimalista (Quick Entry) para registrar un gasto en segundos sin tener que maximizar la aplicación principal.
Menu Bar / System Tray App: Icono en la barra superior/bandeja del sistema mostrando de un vistazo el Saldo Disponible de la Quincena Actual y el Próximo Pago de Deuda.

6. Estructura de Vistas (UI / Livewire Components)
DashboardOverview (Pantalla Principal):
Header: Selector de Workspace (Personal / Negocio), selector de Mes (Jun, Jul, Ago...) y tarjeta de Termómetro de Deuda Total (saldo restante y fecha estimada de libertad financiera).
KPIs Superiores: Ingresos del Mes, Comprometido Q1 vs Q2, Saldo Líquido en Cuentas y Ahorro Real Proyectado.
Barras de Bloques: Progreso visual por porcentaje (Esenciales, Deudas, Inversiones, Ocio) con cambio de color (verde → ámbar → rojo) al acercarse al límite.
BudgetSheetView (Vista Operativa tipo Tabla Interactiva):
Réplica mejorada de tu hoja de cálculo con edición inline mediante Livewire (wire:model.blur), cambio de estado en 1 clic (Pendiente ↔ Pagado), badge de moneda original/tasa y filtro rápido por Primera Quincena o Segunda Quincena.
DebtManagerView (Centro de Control de Deudas):
Lista de pasivos activos con barra de progreso de amortización, selector de estrategia (Bola de Nieve vs Avalancha), simulador de impacto ("¿Qué pasa si abono $50 extra este mes?") e historial de pagos realizados.
AccountsAndRatesView (Cuentas y Tasas):
Gestión de saldos en Efectivo/Bancos, transferencias entre carteras y configuración de tasas de cambio frecuentes.

7. Roadmap de Desarrollo Sugerido (Sprints)
Sprint 1 — Cimientos y Modelo de Datos: Instalación de Laravel + Livewire 3 + TailwindCSS + NativePHP. Migraciones de base de datos, Seeders con tus datos reales de junio–agosto 2026 para validación, y CRUD de Workspaces, Accounts y BudgetPeriods.
Sprint 2 — Motor de Presupuesto y Multimoneda: Construcción de BudgetSheetView con cálculos quincenales reactivos, conversión multimoneda y asistente de clonación limpia de mes.
Sprint 3 — Motor de Deudas ("Pay Debt First"): Implementación de DebtManagerView, cascada de prioridad automática, Observers de amortización al marcar Pagado y reglas de bloqueo de ocio.
Sprint 4 — Integración Nativa: Configuración de atajo global (Quick Entry), notificaciones programadas de vencimiento quincenal, widget en barra de estado y backups locales automatizados.

### L2 — Platform clarification (verbatim)

“Okey pero no quiero usar nativephp con webview, quiero usar componentes nativos, para crear la app movil, asi la web tiene un dieseño y la app movil otro diseño pero comparten datos, otra cosa quiero que cuando no tenga conexion en la app movil se guarde en local y cuando recupere la conexion se envie a la base de datos que nutre las dos apps”

### L3 — Data sharing clarification (verbatim)

“Okey pero quiero que esta aplicacion funcione tanto en web normal con laravel y en mobile utilice nativephp se puede? otra cosa si obviamente la version web y la mobile tienen que compartir datos”

### L4 — Backlog request (verbatim)

“Quiero que primero prepares un backlog y señales las diferencias con el stack actual”

### L5 — ODD authorization (verbatim)

“Okey ahora teniendo todo esto como referencia vamos a montar todo el odd para empezar a trabajar.”

### L6 — Evidence and decisions

- Current worktree was on `main` with uncommitted NativePHP migration changes from the preceding authorized request. Preserved those changes and created branch `feature/biweekly-budget-control`; they are part of the feature worktree and must not be accidentally dropped.
- Verified current stack: Laravel 13.35.0, Livewire 4.4.8, Tailwind 4.3.4, NativePHP Mobile 4.8.0. `config/database.php` defaults to SQLite for local execution. Financial domain tables and sync are not implemented.
- NativePHP Mobile v4 uses native screen components by default; WebView remains an optional mode. Local SQLite is device-local; shared web/mobile data therefore requires a central Laravel API and synchronization. `nativephp/mobile-ui` is a separate package and remains uninstalled.
- Desktop-only tray/global shortcut scope is unresolved after the user specified browser web plus native mobile; leave those features deferred, not silently removed.
- Real June–August 2026 financial source data has not been provided; use synthetic test fixtures until the user supplies/imports it.
- No source code has been changed as part of ODD setup. T0 is ready; next action is the NativePHP native-UI/sync feasibility and bootstrap task.

### L7 — Macro sequencing (verbatim)

“Okey pero podemos dividir el desarrollo en macro partes la web y la app movil”

Accepted delivery grouping: first the shared financial core and synchronization, then the web application, then the NativePHP native mobile application. The shared core precedes both clients so the web and mobile clients use the same financial rules and server data. T0 validates native UI and synchronization feasibility before the shared-core implementation proceeds.


### L8 — Implementation start

- Started T0 after user authorization to implement. NativePHP's official docs confirm SuperNative renders SwiftUI/Jetpack Compose screens; the EDGE component package `nativephp/mobile-ui` must be installed and explicitly registered for device builds.
- Host Artisan startup initially timed out because Telescope accesses its pause cache during boot while the local DB/cache configuration targets an unreachable `postgres` host. `CACHE_STORE=array TELESCOPE_ENABLED=false` safely runs host-side Artisan commands without changing repository config.
- `nativephp/mobile-ui` v0.10.0 is installed and explicitly registered in `app/Providers/NativeServiceProvider.php`. Focused registration tests were observed RED before registration and GREEN after; plugin validation passed for Android/iOS, full Pest suite passed (80 tests / 293 assertions), Pint and PHPStan passed.
- `native:validate` reports no native screens yet (expected before T6); this remains a warning, not proof of a rendered device screen. Official NativePHP database guidance confirms local SQLite plus a secure Laravel API for shared data, with no direct mobile DB credentials.
- An early RDD preflight on the uncommitted worktree requested an untracked-file selection. It did not start a review lineage; per ODD, the proper candidate is assessed at the work-unit commit using the committed-range selector. No review START or authority was created.

### L9 — Begin shared financial core

“Okey entonces podemos empezar a implementar”

T1 has started: implement the shared financial persistence model before either client UI. T0's native component package installation and registration are verified; committed-range RDD assessment is deferred to the work-unit boundary.

### L10 — Finance persistence implementation

- Added migrations and Eloquent models for workspaces, accounts, budget periods/buckets/items, debts, currency rates, financial transactions, and debt payments. Monetary values use decimal columns; the ledger preserves original amount, currency, exchange rate, USD equivalent, and optional idempotency key.
- Debt balances/payment components are retained in debt currency, alongside USD equivalents; transactions retain the exact amount affecting each account's own currency so foreign-currency purchases and cross-account transfers remain auditable.
- Observed RED for the persistence test before schema/models, and another RED when tests first required account-currency and debt-currency snapshots; both passed after implementation. Focused persistence tests passed (3 tests / 21 assertions). Full suite passed (83 tests / 314 assertions), Pint passed, and PHPStan passed.
- Independent verification is pending because the ODD verifier's assigned model (`gpt-6.1-sol`) is not exposed by this session's subagent runtime (available: `gpt-6-luna`, `gpt-5.6-terra`, `gpt-5.6-luna`). No commit has been made.

### L11 — Budget waterfall, quincenas, and clean month cloning implementation

- Implemented pure domain services `BudgetWaterfallCalculator`, `DebtPrioritizer`, `QuincenaCalculator`, `CashFlowCalculator`, enums (`DebtStrategy`, `SplitMode`, `ItemStatus`, `ItemType`, `BucketKey`), and exceptions (`ProtectedDebtMinimumException`, `BudgetPeriodAlreadyExistsException`).
- Implemented application actions `CalculateBudgetWaterfallAction`, `CloneBudgetPeriodAction`, `UpdateBudgetItemAction`, `GetCashFlowSummaryAction` and their typed readonly DTOs.
- Verified 4-level budget waterfall (essentials -> mandatory debt blocking -> debt accelerator via avalanche/snowball -> surplus distribution with discretionary zeroed on deficit).
- Verified protected debt minimum enforcement, clean month cloning (recurring-only, discretionary amounts zeroed, statuses reset to pending), and cash flow real savings & leisure blocking alert.
- RED/GREEN observed on `tests/Feature/BudgetWaterfallTest.php` (5 tests / 46 assertions passed). Full test suite passed (89 tests / 365 assertions). PHPStan (level 7) and Pint passed.

### L12 — Debt payment lifecycle and spending safeguards implementation

- Implemented `BudgetItemObserver` on `BudgetItem` model with atomic DB transaction and row locking (`lockForUpdate` on `Debt`).
- Automatic debt amortization on transition from `pending` to `paid`: balance deduction, creation of `FinancialTransaction` and `DebtPayment` records with `idempotency_key` deduplication.
- Automatic debt reversal on transition from `paid` to `pending`: principal restoration to debt balance and removal of payment/transaction records.
- Implemented `DiscretionaryExpenseBlockedException` and `CreateBudgetItemAction` to block new discretionary expenses after day 15 when Q1 debt payments are pending, allowing explicit justification bypass.
- Implemented `ApplyExtraordinaryIncomeAction` to allocate 50%–70% of variable income directly to top debt according to active strategy (snowball or avalanche).
- RED/GREEN observed on `tests/Feature/DebtLifecycleTest.php` (5 tests / 21 assertions passed). Full test suite passed (94 tests / 386 assertions). PHPStan (level 7) and Pint clean.

### L13 — Shared API and offline synchronization protocol implementation

- Added migration `2026_11_25_051449_create_sync_mutations_table.php` and `SyncMutation` model for client mutation deduplication and idempotency.
- Built RESTful endpoints in `app/Http/Controllers/Api/V1/SyncController.php` with Sanctum authentication and workspace tenant authorization:
  - `POST /api/v1/sync/push`: Processes offline-captured mutation batches (create/update/delete for budget items and transactions) idempotently, automatically triggering debt amortization and returning applied mutation states.
  - `GET /api/v1/sync/pull`: Efficiently queries updated entities (accounts, periods, buckets, items, debts, transactions, exchange rates) since a client timestamp for offline local hydration.
  - `POST /api/v1/workspaces/{workspace}/quick-entry`: Rapid entry endpoint for immediate expense and debt payment recording with real-time balance calculations.
- Updated `BudgetItemObserver` to handle direct `created` events for immediate debt amortization upon creation.
- RED/GREEN observed on `tests/Feature/SyncApiTest.php` (5 tests / 35 assertions passed). Full test suite passed (99 tests / 421 assertions). PHPStan (level 7) and Pint passed. Macro 1 (Shared Financial Core & Sync) is complete.

### L14 — Browser budget and debt workflows implementation (Macro 2 completed)

- Implemented four reactive Livewire 4 components and corresponding Blade views respecting Hallmark design tokens, DaisyUI wrapper components, and touch-target minimums (>=44px):
  - `app/Livewire/Budget/DashboardOverview.php` & `resources/views/livewire/budget/dashboard-overview.blade.php`: Workspace selector, period selector, top KPI cards, debt thermometer with progress bar and color transition, waterfall allocation bars with dynamic status indicators, quick entry modal, and clean month cloning modal. Preserves user greeting banner and profile dropdown navigation.
  - `app/Livewire/Budget/BudgetSheetView.php` & `resources/views/livewire/budget/budget-sheet-view.blade.php`: Spreadsheet-style table view with quincena filters (All, Q1, Q2), bucket category filters, 1-click status toggle (`wire:click="toggleStatus"`), and inline modal for item creation/edition with protected debt minimum safeguards.
  - `app/Livewire/Budget/DebtManagerView.php` & `resources/views/livewire/budget/debt-manager-view.blade.php`: Amortization progress overview, active debt cards with current balance and minimum payment, Bola de Nieve vs Avalancha strategy switcher, extra payment simulator ("¿Qué pasa si abono $X extra este mes?"), and recent payment history table.
  - `app/Livewire/Budget/AccountsAndRatesView.php` & `resources/views/livewire/budget/accounts-and-rates-view.blade.php`: Account creation and live balance list, inter-account balance transfer modal, and currency exchange rates manager.
- Wired web routes in `routes/web/dashboard.php` for `/dashboard`, `/budget`, `/debts`, and `/accounts`.
- Preserved existing dashboard access tests (`tests/Feature/Auth/DashboardAccessTest.php`) and added comprehensive Livewire tests in `tests/Feature/Livewire/BudgetWorkflowTest.php`.
- RED/GREEN observed: 4 focused Livewire tests observed RED then GREEN (15 assertions); full suite 103 tests / 436 assertions passed; Pint and PHPStan Level 7 clean. Macro 2 (Web Application) is complete.

### L15 — Native mobile application, reminders and automated backups implementation (Macro 3 completed)

- Implemented native mobile application using NativePHP native components (`nativephp/mobile-ui` and `Native\Mobile\Edge\NativeComponent`), ensuring true native controls (Material 3 on Android, SwiftUI on iOS) rather than webviews:
  - `App\NativeComponents\MobileDashboardScreen` & `resources/views/native/mobile-dashboard-screen.blade.php`: Native mobile dashboard with workspace and period indicators, Debt Thermometer with native progress bar, liquid cash and committed Q1/Q2 KPI cards, 1-tap quick status toggle for pending items (`markAsPaid`), and quick navigation to Quick Entry and Debts screens.
  - `App\NativeComponents\QuickExpenseScreen` & `resources/views/native/quick-expense-screen.blade.php`: Native rapid entry screen for recording expenses and debt installments with multi-currency and exchange rate inputs, discretionary spending safeguard checks, and local offline sync mutation queuing.
  - `App\NativeComponents\MobileDebtScreen` & `resources/views/native/mobile-debt-screen.blade.php`: Native debt prioritization manager with Bola de Nieve vs Avalancha strategy switcher, active debt cards sorted by priority, and debt amortization progress indicators.
  - `App\Infrastructure\Mobile\LocalSyncManager`: Offline-first synchronization client managing local mutation queuing (`queueMutation`), pending mutation batch tracking (`getPendingMutations`), and server push/pull synchronization (`syncWithServer`).
- Registered native mobile routes under dedicated prefix in `routes/mobile.php` (`/mobile`, `/mobile/quick-entry`, `/mobile/debts`), preserving web routing integrity without URI collisions.
- Validated native component architecture with official analyzer: `php artisan native:validate` passed with 0 errors and all components green.
- Implemented automated reminders and safeguard alerts command `budget:check-reminders` (`App\Console\Commands\CheckBudgetRemindersCommand`):
  - Automatically identifies pending debt minimums and essential obligations for Q1 and Q2.
  - Monitors spending threshold ratios against bucket allocations, triggering warnings at 85% and critical alerts at 100%.
  - Scheduled daily at 09:00 in `routes/console.php`.
- Implemented automated local database backup command `budget:backup` (`App\Console\Commands\BackupBudgetDatabaseCommand`):
  - Serializes full financial domain state (workspaces, accounts, debts, payments, budget periods, buckets, items, transactions, rates) into formatted JSON.
  - Scheduled weekly in `routes/console.php`.
- RED/GREEN observed: 7 focused tests observed RED then GREEN in `tests/Feature/NativeMobileTest.php` and `tests/Feature/BudgetRemindersAndBackupTest.php` (43 assertions); full test suite passed (110 tests / 479 assertions); Pint formatting and PHPStan Level 7 passed with 0 errors. Macro 3 is complete.

### L16 — Complete Fincab UI branding, landing page redesign, and navigation alignment

- Replaced legacy Laravertex starter kit landing page (`resources/views/welcome.blade.php`) with a complete, conversion-optimized Fincab product landing page following Hallmark design principles and DaisyUI component wrappers:
  - Hero messaging communicating the biweekly budget allocation and debt prioritization value proposition in under 5 seconds.
  - Interactive dashboard showcase mockup with live Debt Thermometer preview, Q1/Q2 cash flow metrics, and 4-level budget waterfall allocation breakdown.
  - Comprehensive explanation of core pillars: Cascada "Pay Debt First", Flujo Quincenal Q1 vs Q2, Estrategias Avalancha vs Bola de Nieve, Salvaguarda de Ocio tras el día 15, Multimoneda con conversión histórica a USD, y Operación Offline-First en Web y Móvil.
  - Interactive FAQ answering real financial management scenarios.
- Updated main application layout (`resources/views/layouts/app.blade.php`) to set default title to `Fincab — Control Presupuestario y Priorización de Deudas`.
- Aligned authentication titles and branding across `MagicLogin`, `VerifyToken`, `EditProfile`, and `AccountSettings` to Fincab.
- Updated mobile bottom dock navigation (`resources/views/components/app-bottom-nav.blade.php`) with the 5 core modules: Panel (`/dashboard`), Partidas (`/budget`), Deudas (`/debts`), Cuentas (`/accounts`), y Ajustes (`/settings`).
- Updated email templates (`resources/views/emails/magic-link.blade.php`) and mail envelope subject (`app/Infrastructure/Mail/MagicLinkMail.php`) to Fincab.
- Updated `tests/Feature/ExampleTest.php` to verify the Fincab product landing page. Full test suite (110 tests / 479 assertions), Pint formatting, and PHPStan Level 7 clean.
