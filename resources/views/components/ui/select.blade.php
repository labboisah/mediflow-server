@props([
    'label' => null,
    'name' => null,
])

<label class="block">
    @if($label)
        <span class="mb-1 block text-sm font-medium text-med-ink">{{ $label }}</span>
    @endif

    <select name="{{ $name }}" {{ $attributes->merge(['class' => 'mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm disabled:bg-med-canvas disabled:text-med-muted']) }}>
        {{ $slot }}
    </select>

    @if($name)
        @error($name)
            <span class="mt-1 block text-sm text-med-danger">{{ $message }}</span>
        @enderror
    @endif
</label>
