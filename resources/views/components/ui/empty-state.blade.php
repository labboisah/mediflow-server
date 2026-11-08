@props([
    'title' => 'No records found',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-md border border-dashed border-med-line bg-white px-6 py-10 text-center']) }}>
    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-md bg-med-canvas text-med-primary">
        <i class="bi bi-inbox"></i>
    </div>
    <h3 class="text-base font-semibold text-med-ink">{{ $title }}</h3>
    @if($message)
        <p class="mx-auto mt-1 max-w-md text-sm text-med-muted">{{ $message }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
