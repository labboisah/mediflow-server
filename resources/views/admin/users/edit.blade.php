@extends('layouts.modern')

@section('title', 'Edit User: ' . $user->name)
@section('page-title', 'Edit User')
@section('page-subtitle', 'Update staff account details, department, password, and assigned roles.')

@section('content')
    <x-ui.page title="Edit User" subtitle="Update staff account details, department, password, and assigned roles.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="{{ $user->name }}" subtitle="{{ $user->email }}">
                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Full Name" name="name" value="{{ old('name', $user->name) }}" required />
                        <x-ui.input label="Email" name="email" type="email" value="{{ old('email', $user->email) }}" required />
                        <x-ui.input label="Set Password" name="password" type="password" placeholder="Leave blank to keep existing" />
                        <x-ui.input label="Confirm Password" name="password_confirmation" type="password" />

                        <div class="md:col-span-2">
                            <x-ui.select label="Department" name="department_id" required>
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </div>

                    <section class="rounded-md border border-med-line bg-white">
                        <div class="border-b border-med-line bg-med-canvas px-4 py-3">
                            <h2 class="text-base font-semibold text-med-ink">Assigned Roles</h2>
                        </div>
                        <div class="grid gap-3 p-4 md:grid-cols-2">
                            @forelse ($roles as $role)
                                <label class="flex cursor-pointer items-start gap-3 rounded-md border border-med-line bg-white p-3 transition hover:bg-med-canvas">
                                    <input class="mt-1 h-4 w-4 rounded border-med-line text-med-primary"
                                           type="checkbox"
                                           name="roles[]"
                                           value="{{ $role->id }}"
                                           @checked(in_array($role->id, old('roles', $userRoles)))>
                                    <span>
                                        <span class="block font-semibold text-med-ink">{{ $role->display_name ?: $role->name }}</span>
                                        @if($role->description)
                                            <span class="mt-1 block text-sm text-med-muted">{{ $role->description }}</span>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <x-ui.empty-state title="No roles available" />
                            @endforelse
                        </div>
                    </section>

                    <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-med-info">
                        Select one or more roles to assign permissions to this user. Licensed module access is managed from Access Control.
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Update User
                        </x-ui.button>

                        <a href="{{ route('admin.users.index') }}">
                            <x-ui.button type="button" variant="secondary">Cancel</x-ui.button>
                        </a>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card class="xl:col-span-4" title="Current Roles">
                @if (count($userRoles) > 0)
                    <div class="space-y-3">
                        @foreach ($user->roles as $role)
                            <div class="rounded-md border border-med-line bg-white p-3">
                                <x-ui.badge variant="info">{{ $role->display_name ?: $role->name }}</x-ui.badge>
                                @if($role->description)
                                    <p class="mt-2 text-sm text-med-muted">{{ $role->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="No roles assigned" />
                @endif
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
