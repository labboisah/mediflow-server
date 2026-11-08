@extends('layouts.modern')

@section('title', 'Investigations')
@section('page-title', 'Investigations')
@section('page-subtitle', 'Manage billable laboratory and diagnostic investigations by department and type.')

@section('content')
    <x-ui.page title="Investigations" subtitle="Manage billable laboratory and diagnostic investigations by department and type.">
        <x-slot:actions>
            <a href="{{ route('admin.investigations.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    New Investigation
                </x-ui.button>
            </a>
        </x-slot:actions>

        <div class="space-y-6">
            @forelse ($departments as $department)
                @php
                    $types = $department->investigationTypes->filter(fn ($type) => $type->investigations->isNotEmpty());
                @endphp

                @if($types->isNotEmpty())
                    <x-ui.card title="{{ $department->name }} Investigations">
                        <x-ui.table class="shadow-none">
                            <thead class="bg-med-canvas">
                                <tr>
                                    <th class="w-20 px-4 py-3 text-left text-sm font-semibold text-med-ink">#</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Investigation</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Price</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Parameters</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-med-line">
                                @foreach($types as $type)
                                    <tr class="bg-white">
                                        <td colspan="5" class="px-4 py-3">
                                            <div class="flex items-center gap-2 font-semibold text-med-ink">
                                                <i class="bi bi-folder2-open text-med-primary"></i>
                                                {{ $type->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($type->investigations as $investigation)
                                        <tr class="hover:bg-med-canvas">
                                            <td class="px-4 py-4 text-sm font-medium text-med-muted">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-4 font-semibold text-med-ink">{{ $investigation->name }}</td>
                                            <td class="px-4 py-4 text-right font-semibold text-med-ink">{{ number_format($investigation->price, 2) }}</td>
                                            <td class="px-4 py-4 text-center">
                                                <x-ui.badge>{{ $investigation->parameters_count }}</x-ui.badge>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ route('admin.investigations.edit', $investigation) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('admin.investigations.destroy', $investigation) }}" method="POST" onsubmit="return confirm('Delete this Investigation?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    </x-ui.card>
                @endif
            @empty
                <x-ui.empty-state title="No departments found" />
            @endforelse

            @if($departments->flatMap->investigationTypes->flatMap->investigations->isEmpty())
                <x-ui.empty-state title="No investigations found" />
            @endif
        </div>
    </x-ui.page>
@endsection
