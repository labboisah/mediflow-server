@php
    $requests = auth()->user()->department->requestStats();
    $revenue = auth()->user()->department->revenue();
@endphp

<section class="space-y-6">
    <x-ui.card title="Investigation Request Overview">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Total Request Today</p>
                <p class="mt-2 text-2xl font-semibold text-med-ink">{{ number_format($requests['today']) }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Paid Request</p>
                <p class="mt-2 text-2xl font-semibold text-med-success">{{ number_format($requests['paid']) }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Payment in Progress</p>
                <p class="mt-2 text-2xl font-semibold text-med-warning">{{ number_format($requests['payment_in_progress']) }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Pending Request</p>
                <p class="mt-2 text-2xl font-semibold text-med-warning">{{ number_format($requests['pending']) }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Completed Request</p>
                <p class="mt-2 text-2xl font-semibold text-med-primary">{{ number_format($requests['completed']) }}</p>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Revenue Generated">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Today's Revenue</p>
                <p class="mt-2 text-2xl font-semibold text-med-success">{{ $revenue['today'] }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">This Month's Revenue</p>
                <p class="mt-2 text-2xl font-semibold text-med-success">{{ $revenue['this_month'] }}</p>
            </div>
            <div class="rounded-md border border-med-line bg-med-canvas/60 p-4">
                <p class="text-sm font-medium text-med-muted">Total Revenue</p>
                <p class="mt-2 text-2xl font-semibold text-med-success">{{ $revenue['total'] }}</p>
            </div>
        </div>
    </x-ui.card>
</section>
