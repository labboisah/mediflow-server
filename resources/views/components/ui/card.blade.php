@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-md border border-med-line bg-white shadow-sm']) }}>
    @if($title || $subtitle || $actions)
        <div class="flex flex-col gap-3 border-b border-med-line px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if($title)
                    <h2 class="text-lg font-semibold leading-6 text-med-ink">{{ $title }}</h2>
                @endif

                @if($subtitle)
                    <p class="mt-1 text-base leading-6 text-med-muted">{{ $subtitle }}</p>
                @endif
            </div>

            @if($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</section>
