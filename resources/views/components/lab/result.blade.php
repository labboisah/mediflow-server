<section class="space-y-7">
    <div class="flex flex-col gap-5 border-b border-med-line pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-semibold leading-tight text-med-ink">Laboratory Result Entry</h1><p class="mt-2 max-w-3xl text-base leading-6 text-med-muted">Search a paid bill and record result values for its laboratory investigations.</p></div>
        @if($loaded && $bill)<a href="{{ route('lab.requests.show', $bill) }}" target="_blank" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-printer"></i>Print Report</a>@endif
    </div>

    <x-ui.card title="Find Bill">
        <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end"><x-ui.input label="Bill Number" wire:model="bill_number" wire:keydown.enter="search" placeholder="Enter bill number" /><x-ui.button type="button" wire:click="search" wire:loading.attr="disabled"><span wire:loading.remove wire:target="search"><i class="bi bi-search"></i>Search</span><span wire:loading wire:target="search">Searching...</span></x-ui.button></div>
        <p wire:loading wire:target="search" class="mt-3 text-sm text-med-muted">Searching investigation requests...</p>
    </x-ui.card>

    @if($bill)
        <x-ui.card title="Investigation Summary">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"><div><p class="text-sm text-med-muted">Bill Number</p><p class="font-semibold text-med-ink">{{ $bill->bill_number }}</p></div><div><p class="text-sm text-med-muted">Total Investigations</p><p class="font-semibold text-med-ink">{{ count($requests) }}</p></div><div class="xl:col-span-2"><p class="text-sm text-med-muted">Patient Information</p>@if($patient)<p class="font-semibold text-med-ink">{{ $patient->name() }}</p><p class="text-sm text-med-muted">{{ $patient->demographic?->gender ?? 'N/A' }} | {{ $patient->demographic?->phone_number ?? 'N/A' }}</p>@elseif($walkin)<p class="font-semibold text-med-ink">{{ $walkin->name }}</p><p class="text-sm text-med-muted">{{ $walkin->phone_number }} | {{ $walkin->address }}</p>@else<p class="text-sm text-med-muted">N/A</p>@endif</div></div>
        </x-ui.card>
    @endif

    @if($loaded && count($requests))
        @foreach($requests as $request)
            <x-ui.card title="{{ $request->investigation->name }}" subtitle="Requested: {{ $request->created_at->format('d M Y h:i A') }}">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div class="grid flex-1 gap-4 md:grid-cols-4"><x-ui.input label="Lab Number" wire:model="labNumbers.{{ $request->id }}" /><div><p class="text-sm font-medium text-med-ink">Specimen</p><p class="mt-2 text-sm text-med-muted">{{ $request->specimen ?? 'N/A' }}</p></div><div><p class="text-sm font-medium text-med-ink">Requested By</p><p class="mt-2 text-sm text-med-muted">{{ $request->requestedBy->name ?? 'N/A' }}</p></div><div><p class="text-sm font-medium text-med-ink">Performed By</p><p class="mt-2 text-sm text-med-muted">{{ $request->performedBy->name ?? 'N/A' }}</p></div></div>@if($request->status == 'Completed')<x-ui.badge variant="success">Completed</x-ui.badge>@elseif($request->status == 'Pending')<x-ui.badge variant="warning">Pending</x-ui.badge>@else<x-ui.badge>{{ $request->status }}</x-ui.badge>@endif</div>
                @if($request->clinical_diagnoses)<div class="mb-5 rounded-md border border-med-line bg-med-canvas p-4 text-sm text-med-muted">{{ $request->clinical_diagnoses }}</div>@endif
                <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Parameter</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Reference Range</th><th class="px-4 py-3">Result Value</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-med-line bg-white">@foreach($request->investigation->parameters as $parameter)<tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $parameter->name }}</td><td class="px-4 py-4 text-med-muted">{{ $parameter->unit }}</td><td class="px-4 py-4 text-med-muted">{{ $parameter->reference_range }}</td><td class="px-4 py-4"><input type="text" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2 text-sm text-med-ink shadow-sm" wire:model.defer="results.{{ $request->id }}.{{ $parameter->id }}"></td><td class="px-4 py-4 text-right"><button type="button" class="text-sm font-semibold text-med-danger hover:text-red-800" wire:click="deleteResult({{ $request->id }}, {{ $parameter->id }})">Delete</button></td></tr>@endforeach</tbody></x-ui.table>
                <div class="mt-5 flex justify-end"><x-ui.button type="button" wire:click="saveInvestigation({{ $request->id }})"><i class="bi bi-save"></i>Save Result</x-ui.button></div>
            </x-ui.card>
        @endforeach
    @elseif($loaded)
        <x-ui.empty-state title="No Investigation Request" message="No investigation request was found for this bill number." />
    @endif
</section>
