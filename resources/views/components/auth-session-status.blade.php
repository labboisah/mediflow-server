@props(['status'])

@if ($status)
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert" {{ $attributes }}>
        <i class="bi bi-check-circle-fill me-2" style="color: #27AE60;"></i>
        <div>
            {{ $status }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
