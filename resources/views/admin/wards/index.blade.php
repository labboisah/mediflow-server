@extends('layouts.modern')

@section('title', 'Wards')
@section('page-title', 'Wards')
@section('page-subtitle', 'Manage ward capacity, daily prices, and bed availability.')

@section('content')
    <x-ui.page title="Wards" subtitle="Manage ward capacity, daily prices, and bed availability.">
        <x-slot:actions>
            <a href="{{ route($routePrefix.'.wards.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    New Ward
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="w-20 px-4 py-3 text-left text-sm font-semibold text-med-ink">#</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Ward</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Price</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Capacity</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Occupied</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Vacant</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse ($wards as $ward)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4 text-sm font-medium text-med-muted">{{ $loop->iteration }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <span class="mf-icon-box">
                                    <i class="bi bi-hospital"></i>
                                </span>
                                <span class="font-semibold text-med-ink">{{ $ward->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right font-semibold text-med-ink">{{ number_format($ward->price, 2) }}</td>
                        <td class="px-4 py-4 text-center"><x-ui.badge>{{ $ward->capacity }}</x-ui.badge></td>
                        <td class="px-4 py-4 text-center"><x-ui.badge variant="warning">{{ $ward->occupied_beds_count }}</x-ui.badge></td>
                        <td class="px-4 py-4 text-center"><x-ui.badge variant="success">{{ $ward->vacant_beds_count }}</x-ui.badge></td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route($routePrefix.'.wards.edit', $ward->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit ward">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route($routePrefix.'.beds.index', $ward) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 text-sm font-semibold text-med-primary hover:bg-med-canvas" title="View beds">
                                    <i class="bi bi-eye"></i>
                                    Beds
                                </a>
                                <form action="{{ route($routePrefix.'.wards.destroy', $ward) }}" method="POST" onsubmit="return confirm('Delete this ward?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete ward">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8">
                            <x-ui.empty-state title="No wards found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.page>
@endsection
