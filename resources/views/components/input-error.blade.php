@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'invalid-feedback d-block']) }}>
        @foreach ((array) $messages as $message)
            <div class="d-flex align-items-center" style="margin-bottom: 0.25rem;">
                <i class="bi bi-exclamation-circle me-2" style="color: #dc3545;"></i>
                <span>{{ $message }}</span>
            </div>
        @endforeach
    </div>
@endif
