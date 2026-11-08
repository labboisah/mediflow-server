@if($feedbackMessage)
    @php
        $feedbackClasses = [
            'success' => 'border-green-200 bg-green-50 text-green-800',
            'warning' => 'border-orange-200 bg-orange-50 text-orange-800',
            'danger' => 'border-red-200 bg-red-50 text-red-800',
            'error' => 'border-red-200 bg-red-50 text-red-800',
            'info' => 'border-blue-200 bg-blue-50 text-blue-800',
        ][$feedbackType] ?? 'border-med-line bg-med-canvas text-med-ink';
    @endphp

    <div class="mb-5 flex items-start justify-between gap-3 rounded-md border px-4 py-3 text-sm font-medium {{ $feedbackClasses }}" role="alert">
        <span>{{ $feedbackMessage }}</span>
        <button type="button" class="mf-focus rounded-md px-2 text-lg leading-none opacity-70 transition hover:opacity-100" wire:click="$set('feedbackMessage', null)" aria-label="Dismiss notification">&times;</button>
    </div>
@endif
