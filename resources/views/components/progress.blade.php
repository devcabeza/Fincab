@props([
    'variant' => 'primary',
    'value' => 0,
    'max' => 100,
])

@php
$baseClasses = 'progress w-full transition-all duration-300';

$variantClasses = match ($variant) {
    'primary' => 'progress-primary',
    'secondary' => 'progress-secondary',
    'accent' => 'progress-accent',
    'success' => 'progress-success',
    'warning' => 'progress-warning',
    'error', 'danger' => 'progress-error',
    'info' => 'progress-info',
    default => 'progress-primary',
};
@endphp

<progress
    {{ $attributes->class([$baseClasses, $variantClasses]) }}
    value="{{ $value }}"
    max="{{ $max }}"
></progress>
