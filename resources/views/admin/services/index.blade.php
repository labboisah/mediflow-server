@extends('layouts.modern')

@section('title', 'Services')
@section('page-title', 'Services')
@section('page-subtitle', 'Manage billable services, prices, departments, categories, and availability.')

@section('content')
    <x-ui.page title="Services" subtitle="Manage billable services, prices, departments, categories, and availability.">
        <x-slot:actions>
            <a href="{{ route('admin.services.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    Add Service
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.card>
            <form method="GET" action="{{ route('admin.services.index') }}" class="grid gap-4 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-4">
                    <x-ui.input label="Search" type="search" name="search" value="{{ request('search') }}" placeholder="Search code, name, category, or description" />
                </div>

                <div class="xl:col-span-3">
                    <x-ui.select label="Category" name="category">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') == $cat)>{{ $cat }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="xl:col-span-2">
                    <x-ui.select label="Status" name="status">
                        <option value="">All status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </x-ui.select>
                </div>

                <label class="flex items-center gap-3 rounded-md border border-med-line bg-white px-4 py-3 text-sm font-medium text-med-muted xl:col-span-2">
                    <input class="h-4 w-4 rounded border-med-line text-med-primary" type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))>
                    Show deleted
                </label>

                <div class="flex gap-2 xl:col-span-1">
                    <x-ui.button type="submit" variant="secondary" class="h-11 flex-1 px-0" title="Apply filters">
                        <i class="bi bi-funnel"></i>
                    </x-ui.button>
                    <a href="{{ route('admin.services.index') }}" class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-med-line bg-white text-med-muted hover:bg-med-canvas" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </x-ui.card>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Code</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Service</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Department</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Category</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Price</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse($services as $service)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4"><x-ui.badge>{{ $service->code }}</x-ui.badge></td>
                        <td class="px-4 py-4">
                            <div>
                                <p class="font-semibold text-med-ink">{{ $service->name }}</p>
                                @if($service->description)
                                    <p class="mt-1 max-w-md truncate text-sm text-med-muted">{{ $service->description }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-med-muted">{{ $service->department->name ?? 'Not assigned' }}</td>
                        <td class="px-4 py-4"><x-ui.badge variant="info">{{ $service->category }}</x-ui.badge></td>
                        <td class="px-4 py-4 text-right font-semibold text-med-ink">{{ number_format($service->price, 2) }}</td>
                        <td class="px-4 py-4">
                            @if($service->trashed())
                                <x-ui.badge variant="danger"><i class="bi bi-trash"></i> Deleted</x-ui.badge>
                            @elseif($service->is_active)
                                <x-ui.badge variant="success"><i class="bi bi-check-circle"></i> Active</x-ui.badge>
                            @else
                                <x-ui.badge variant="warning"><i class="bi bi-dash-circle"></i> Inactive</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                @unless($service->trashed())
                                    <a href="{{ route('admin.services.show', $service) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-muted hover:bg-med-canvas" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.services.edit', $service) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm('Delete this service?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.services.restore', $service->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-primary bg-white text-med-primary hover:bg-green-50" title="Restore">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8">
                            <x-ui.empty-state title="No services found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div>
            {{ $services->links() }}
        </div>
    </x-ui.page>
@endsection
