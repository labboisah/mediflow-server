@extends('layouts.modern')

@section('title', 'Beds')
@section('page-title', $ward->name.' Beds')
@section('page-subtitle', 'Manage bed numbers and occupancy status for this ward.')

@section('content')
    <x-ui.page :title="$ward->name.' Beds'" subtitle="Manage bed numbers and occupancy status for this ward.">
        <x-slot:actions>
            <a href="{{ route($routePrefix.'.beds.create', $ward) }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    New Bed
                </x-ui.button>
            </a>
            <a href="{{ route($routePrefix.'.wards.index') }}">
                <x-ui.button type="button" variant="secondary">
                    <i class="bi bi-arrow-left"></i>
                    Wards
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="w-20 px-4 py-3 text-left text-sm font-semibold text-med-ink">#</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Bed No</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse ($ward->beds as $bed)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4 text-sm font-medium text-med-muted">{{ $loop->iteration }}</td>
                        <td class="px-4 py-4 font-semibold text-med-ink">{{ $bed->bed_no }}</td>
                        <td class="px-4 py-4">
                            <x-ui.badge :variant="$bed->status === 'occupied' ? 'warning' : 'success'">{{ ucfirst($bed->status) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route($routePrefix.'.beds.edit', $bed->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit bed">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route($routePrefix.'.beds.destroy', $bed) }}" method="POST" onsubmit="return confirm('Delete this bed?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete bed">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8">
                            <x-ui.empty-state title="No beds found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.page>
@endsection
