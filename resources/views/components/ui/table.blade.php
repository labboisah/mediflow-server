<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-md border border-med-line bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-med-line text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
