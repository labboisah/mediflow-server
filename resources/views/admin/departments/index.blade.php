@extends('layouts.modern')

@section('title', 'Departments')
@section('page-title', 'Departments')
@section('page-subtitle', 'Manage hospital departments used by users, services, reports, and workflows.')

@section('content')
    <x-ui.page title="Departments" subtitle="Manage hospital departments used by users, services, reports, and workflows.">
        <x-slot:actions>
            <a href="{{ route('admin.departments.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    New Department
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="w-20 px-4 py-3 text-left text-sm font-semibold text-med-ink">#</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Department</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse ($departments as $department)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4 text-sm font-medium text-med-muted">{{ $loop->iteration }}</td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <span class="mf-icon-box">
                                    <i class="bi bi-buildings"></i>
                                </span>
                                <span class="font-semibold text-med-ink">{{ $department->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.departments.edit', $department->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.departments.destroy', $department) }}" method="POST" onsubmit="return confirm('Delete this department?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8">
                            <x-ui.empty-state title="No departments found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.page>
@endsection
