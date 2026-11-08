@props([
    'variant' => 'neutral',
])

@php
    $classes = [
        'primary' => 'bg-med-primary text-white ring-med-primary',
        'success' => 'bg-green-50 text-med-primary ring-green-200',
        'warning' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'danger' => 'bg-red-50 text-med-danger ring-red-200',
        'info' => 'bg-blue-50 text-med-info ring-blue-200',
        'neutral' => 'bg-med-canvas text-med-muted ring-med-line',
    ][$variant] ?? 'bg-med-canvas text-med-muted ring-med-line';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset ' . $classes]) }}>
    {{ $slot }}
</span>
