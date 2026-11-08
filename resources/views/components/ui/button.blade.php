@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = [
        'primary' => 'bg-med-primary text-white hover:bg-med-primaryDark border-med-primary',
        'secondary' => 'bg-white text-med-ink hover:bg-med-canvas border-med-line',
        'danger' => 'bg-med-danger text-white hover:bg-orange-800 border-med-danger',
        'ghost' => 'bg-transparent text-med-muted hover:bg-white hover:text-med-primary border-transparent',
    ][$variant] ?? 'bg-med-primary text-white hover:bg-med-primaryDark border-med-primary';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'mf-focus inline-flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold transition ' . $classes]) }}>
    {{ $slot }}
</button>
