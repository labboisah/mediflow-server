@extends('layouts.modern')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Manage staff accounts, departments, and role assignment.')

@section('content')
    <x-ui.page title="Users" subtitle="Manage staff accounts, departments, and role assignment.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    New User
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.card>
            <form method="GET" class="grid gap-4 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-5">
                    <x-ui.input label="Search" type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email" />
                </div>

                <div class="lg:col-span-4">
                    <x-ui.select label="Role" name="role">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(request('role') == $role->id)>
                                {{ $role->display_name ?: $role->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>

                <label class="flex items-center gap-3 rounded-md border border-med-line bg-white px-4 py-3 text-sm font-medium text-med-muted lg:col-span-2">
                    <input class="h-4 w-4 rounded border-med-line text-med-primary" type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))>
                    Show deleted
                </label>

                <div class="lg:col-span-1">
                    <x-ui.button type="submit" variant="secondary" class="w-full">
                        <i class="bi bi-funnel"></i>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Name</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Email</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Roles</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Department</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse ($users as $user)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <span class="mf-icon-box">
                                    <i class="bi bi-person"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-med-ink">{{ $user->name }}</p>
                                    @if($user->trashed())
                                        <x-ui.badge variant="danger">Deleted</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-med-muted">{{ $user->email }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($user->roles as $role)
                                    <x-ui.badge variant="info">{{ $role->display_name ?: $role->name }}</x-ui.badge>
                                @empty
                                    <span class="text-sm text-med-muted">No roles</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-med-muted">{{ $user->department->name ?? 'Not assigned' }}</td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                @if($user->trashed())
                                    <form action="{{ route('admin.users.restore', $user->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-primary bg-white text-med-primary hover:bg-green-50" title="Restore">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </form>
                                @else
                                    @if ($user->id !== auth()->id())
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-muted hover:bg-med-canvas" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-info hover:bg-med-canvas" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-sm text-med-muted">Your account</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8">
                            <x-ui.empty-state title="No users found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div>
            {{ $users->links() }}
        </div>
    </x-ui.page>
@endsection
