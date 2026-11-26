# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Usuario profesional y autónomo operando en economías de alta volatilidad y múltiples monedas (Venezuela y Colombia), gestionando ingresos y pagos quincenales (Q1 y Q2) entre USD, VES y COP.

## Product Purpose

Fincab es una herramienta personal de control presupuestario y liquidación acelerada de deudas de alto rendimiento. Existe para eliminar la ilusión de liquidez y el descalce cambiario, garantizando que cada dólar generado se asigne a partidas esenciales, amortizaciones estratégicas o ahorro antes de permitir gastos variables o de ocio.

## Positioning

Presupuesto quincenal con cascada estricta de 4 niveles y termómetro de deuda (Bola de nieve y Avalancha), anclado en USD con conversión automática y cotizaciones en vivo (BCV, Paralelo, Binance P2P y TRM COP) sin fricción matemática ni data entry manual.

## Operating Context

- Uso diario y quincenal desde navegador de escritorio y dispositivos móviles.
- Entorno de hiperinflación y dispersión cambiaria donde se compra con tarjetas de débito en VES (tasa BCV), se transacciona P2P en Binance y se reciben pagos en USD o COP.
- Acceso rápido sin contraseñas vía enlaces mágicos (Sendrix / Mailpit).

## Capabilities and Constraints

- **Capacidades**:
  - Control por quincenas (Q1: 1–15, Q2: 16–fin de mes) con división automática 50/50 o asignación específica.
  - Priorización matemática de pasivos (Estrategia Avalancha por mayor interés o Bola de Nieve por menor saldo).
  - Cascada de asignación: Gastos Esenciales -> Cuotas Mínimas de Deuda -> Aceleración de Pasivo Foco -> Ocio/Variables (bloqueado si el presupuesto no cubre prioridades).
  - Integración multi-tasa en vivo con cotización natural (1 USD = X moneda local) y banner global.
  - Registro rápido de gastos y transferencias interbancarias.
  - Comunicaciones transaccionales mediante correos de acceso seguro (Magic Link).
- **Restricciones Técnicas**:
  - Laravel 12 con Livewire y Tailwind CSS v4 CSS-first.
  - No usar clases inline de daisyUI en vistas (`btn btn-primary`, etc.); usar estrictamente los componentes wrapper de `resources/views/components/` (`<x-button>`, `<x-card>`, `<x-input>`, etc.) con touch target mínimo de 44px.
  - Base de datos relacional PostgreSQL con tipos estrictos y arquitectura limpia.
  - Textos y copys orientados al usuario en español; código en inglés.

## Brand Commitments

- **Nombre**: Fincab.
- **Identidad**: Minimalismo fintech oscuro (dark mode prioritario), paleta de alto contraste basada en zinc oscuro con acentos funcionales índigo, esmeralda (liquidez), ámbar (advertencia/paralelo) y rose (deuda/peligro).
- **Tono**: Sobrio, directo, profesional, libre de jerga publicitaria superflua.

## Evidence on Hand

- Código fuente en producción: `app/Livewire/Budget/`, `app/Domain/Budget/`, `resources/views/livewire/budget/`.
- Componentes wrapper existentes en `resources/views/components/`.
- Correos transaccionales en `resources/views/emails/magic-link.blade.php`.
- Landing page en `resources/views/welcome.blade.php`.

## Product Principles

1. **Claridad sobre adorno**: Cada número, balance y porcentaje debe ser legible al instante sin distracciones estéticas que nublen el juicio financiero.
2. **Cero fricción cambiaria**: La tasa de cambio debe fluir en segundo plano; el usuario piensa en su moneda de bolsillo mientras el sistema preserva el valor real en USD.
3. **Disciplina presupuestaria**: Las prioridades no son sugerencias; la cascada protege primero la supervivencia económica y la erradicación de intereses usurarios.
4. **Alta densidad operativa**: En herramientas personales de uso diario, los clics mínimos y la información compacta superan a los espacios vacíos exagerados.
