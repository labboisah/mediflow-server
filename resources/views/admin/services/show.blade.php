@extends('layouts.modern')

@section('title', 'Service Details')
@section('page-title', 'Service Details')
@section('page-subtitle', 'Review service pricing, department ownership, status, and usage.')

@section('content')
    <x-ui.page title="Service Details" subtitle="Review service pricing, department ownership, status, and usage.">
        <x-slot:actions>
            <a href="{{ route('admin.services.edit', $service) }}">
                <x-ui.button>
                    <i class="bi bi-pencil"></i>
                    Edit
                </x-ui.button>
            </a>
            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Delete this service? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger">
                    <i class="bi bi-trash"></i>
                    Delete
                </x-ui.button>
            </form>
            <a href="{{ route('admin.services.index') }}">
                <x-ui.button type="button" variant="secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </x-ui.button>
            </a>
        </x-slot:actions>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="{{ $service->code }} - {{ $service->name }}">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-med-muted">Service Code</p>
                        <div class="mt-2"><x-ui.badge>{{ $service->code }}</x-ui.badge></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Status</p>
                        <div class="mt-2">
                            @if($service->is_active)
                                <x-ui.badge variant="success"><i class="bi bi-check-circle"></i> Active</x-ui.badge>
                            @else
                                <x-ui.badge variant="warning"><i class="bi bi-dash-circle"></i> Inactive</x-ui.badge>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Service Name</p>
                        <p class="mt-1 font-semibold text-med-ink">{{ $service->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Category</p>
                        <div class="mt-2"><x-ui.badge variant="info">{{ $service->category }}</x-ui.badge></div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Department</p>
                        <p class="mt-1 font-semibold text-med-ink">{{ $service->department->name ?? 'Not assigned' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Price</p>
                        <p class="mt-1 text-xl font-bold text-med-primary">{{ number_format($service->price, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Created At</p>
                        <p class="mt-1 text-med-ink">{{ $service->created_at?->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-med-muted">Last Updated</p>
                        <p class="mt-1 text-med-ink">{{ $service->updated_at?->format('M d, Y H:i') }}</p>
                    </div>
                    @if($service->description)
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-med-muted">Description</p>
                            <p class="mt-1 leading-6 text-med-ink">{{ $service->description }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="xl:col-span-4" title="Usage">
                <div class="rounded-md border border-med-line bg-med-canvas p-4">
                    <p class="text-sm font-medium text-med-muted">Number of Bills</p>
                    <p class="mt-2 text-3xl font-bold text-med-ink">{{ $service->bills_count }}</p>
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
