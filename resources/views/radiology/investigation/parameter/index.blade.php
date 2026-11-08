@extends('layouts.modern')

@section('title', 'Radiology Parameters')

@section('content')
    <x-ui.page title="Investigation Parameters" subtitle="{{ $investigation->name }} result parameters and reference ranges.">
        <x-slot name="actions">
            <a href="{{ route('radiology.investigations.parameters.create', $investigation) }}" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                New Parameter
            </a>
            <a href="{{ route('radiology.investigations.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                Back to Investigations
            </a>
        </x-slot>

        <x-ui.card title="{{ $investigation->name }} Parameters">
            <x-ui.table class="shadow-none">
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Reference Range</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse ($investigation->parameters as $parameter)
                        <tr>
                            <td class="px-4 py-4 text-med-muted">{{ $loop->iteration }}</td>
                            <td class="px-4 py-4 font-semibold text-med-ink">{{ $parameter->name }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $parameter->unit }}</td>
                            <td class="px-4 py-4 text-med-muted">{{ $parameter->reference_range }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('radiology.investigations.parameters.edit', [$investigation, $parameter]) }}" class="text-sm font-semibold text-med-info hover:text-blue-700">Edit</a>
                                    <form action="{{ route('radiology.investigations.parameters.destroy', [$investigation, $parameter]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this parameter?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-med-danger hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8">
                                <x-ui.empty-state title="No Parameters" message="Add result parameters for this investigation." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </x-ui.page>
@endsection
