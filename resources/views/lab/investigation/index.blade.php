@extends('layouts.modern')

@section('title', 'Laboratory Investigations')

@section('content')
    <x-ui.page title="Laboratory Investigations" subtitle="Manage investigations and result parameters for {{ auth()->user()->department?->name ?? 'the laboratory' }}.">
        <x-slot:actions><a href="{{ route('lab.investigations.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>New Investigation</a></x-slot:actions>
        <div class="space-y-6">
            @forelse(auth()->user()->department?->investigationTypes ?? collect() as $investigationType)
                <x-ui.card title="{{ $investigationType->name }}" subtitle="{{ $investigationType->investigations->count() }} investigation{{ $investigationType->investigations->count() === 1 ? '' : 's' }} configured.">
                    <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">Requests</th><th class="px-4 py-3">Parameters</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-med-line bg-white">@forelse($investigationType->investigations as $investigation)<tr><td class="px-4 py-4 text-med-muted">{{ $loop->iteration }}</td><td class="px-4 py-4 text-med-muted">{{ $investigation->code ?? 'N/A' }}</td><td class="px-4 py-4 font-semibold text-med-ink">{{ $investigation->name }}</td><td class="px-4 py-4 text-med-muted">&#8358;{{ number_format((float) $investigation->price, 2) }}</td><td class="px-4 py-4 text-med-muted">{{ number_format($investigation->investigationRequests->count()) }}</td><td class="px-4 py-4"><x-ui.badge variant="info">{{ number_format($investigation->parameters->count()) }}</x-ui.badge></td><td class="px-4 py-4"><div class="flex flex-wrap justify-end gap-2"><a href="{{ route('lab.investigations.parameters.index', $investigation) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Parameters</a><a href="{{ route('lab.investigations.edit', $investigation) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a><form action="{{ route('lab.investigations.destroy', $investigation) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this investigation?');">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-med-danger hover:text-red-800">Delete</button></form></div></td></tr>@empty<tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Investigations" message="Add investigations for this type to begin receiving lab requests." /></td></tr>@endforelse</tbody></x-ui.table>
                </x-ui.card>
            @empty
                <x-ui.empty-state title="No Investigation Types" message="Create investigation types for this department before adding investigations." />
            @endforelse
        </div>
    </x-ui.page>
@endsection
