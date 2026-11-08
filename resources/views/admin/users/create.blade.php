@extends('layouts.modern')

@section('title', 'Create User')
@section('page-title', 'Create User')
@section('page-subtitle', 'Add a staff account and assign department and role access.')

@section('content')
    <x-ui.page title="Create User" subtitle="Add a staff account and assign department and role access.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="User Details">
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Full Name" name="name" value="{{ old('name') }}" required />
                        <x-ui.input label="Email" name="email" type="email" value="{{ old('email') }}" required />
                        <x-ui.input label="Password" name="password" type="password" placeholder="Leave blank to auto-generate" />
                        <x-ui.input label="Confirm Password" name="password_confirmation" type="password" />

                        <div class="md:col-span-2">
                            <x-ui.select label="Department" name="department_id" required>
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </div>

                    <section class="rounded-md border border-med-line bg-white">
                        <div class="border-b border-med-line bg-med-canvas px-4 py-3">
                            <h2 class="text-base font-semibold text-med-ink">Assign Roles</h2>
                        </div>
                        <div class="grid gap-3 p-4 md:grid-cols-2">
                            @forelse($roles as $role)
                                <label class="flex cursor-pointer items-start gap-3 rounded-md border border-med-line bg-white p-3 transition hover:bg-med-canvas">
                                    <input class="mt-1 h-4 w-4 rounded border-med-line text-med-primary"
                                           type="checkbox"
                                           name="roles[]"
                                           value="{{ $role->id }}"
                                           @checked(in_array($role->id, old('roles', [])))>
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

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Create User
                        </x-ui.button>

                        <a href="{{ route('admin.users.index') }}">
                            <x-ui.button type="button" variant="secondary">Cancel</x-ui.button>
                        </a>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card class="xl:col-span-4" title="Account Setup">
                <div class="space-y-4 text-base text-med-muted">
                    <p>Users need a department and at least one role before they can work naturally inside licensed modules.</p>
                    <p>If password is left blank, the system generates one and attempts to email the user.</p>
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
