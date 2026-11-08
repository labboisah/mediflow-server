@extends('layouts.modern')

@section('title', 'Grant Temporary Permission')
@section('page-title', 'Grant Temporary Permission')
@section('page-subtitle', 'Give a user short-lived access to a specific permission.')

@section('content')
    <x-ui.page title="Grant Temporary Permission" subtitle="Give a user short-lived access to a specific permission.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="Permission Grant">
                <form action="{{ route('admin.temporary-permissions.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.select label="User" name="user_id" required>
                            <option value="">Choose a user</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select label="Permission" name="permission_id" required>
                            <option value="">Choose a permission</option>
                            @foreach ($permissions as $permission)
                                <option value="{{ $permission->id }}" @selected(old('permission_id') == $permission->id)>
                                    {{ $permission->module ? $permission->module.' - ' : '' }}{{ $permission->name }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.input label="Duration Hours" name="duration_hours" type="number" min="1" max="168" value="{{ old('duration_hours', 24) }}" required />

                        <div class="md:col-span-2">
                            <x-ui.textarea label="Reason" name="reason" rows="3" maxlength="500" placeholder="Optional audit note">{{ old('reason') }}</x-ui.textarea>
                        </div>
                    </div>

                    <div class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                        Temporary grants expire automatically after the selected duration and can be revoked before expiry.
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-med-line pt-5">
                        <x-ui.button type="submit">
                            <i class="bi bi-check-circle"></i>
                            Grant Permission
                        </x-ui.button>

                        <a href="{{ route('admin.temporary-permissions.index') }}">
                            <x-ui.button type="button" variant="secondary">Cancel</x-ui.button>
                        </a>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
