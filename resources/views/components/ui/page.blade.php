@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<section {{ $attributes->merge(['class' => 'space-y-7']) }}>
    @if($title || $subtitle || $actions)
        <div class="flex flex-col gap-5 border-b border-med-line pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                @if($title)
                    <h1 class="text-3xl font-semibold leading-tight text-med-ink">{{ $title }}</h1>
                @endif

                @if($subtitle)
                    <p class="mt-2 max-w-3xl text-base leading-6 text-med-muted">{{ $subtitle }}</p>
                @endif
            </div>

            @if($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
