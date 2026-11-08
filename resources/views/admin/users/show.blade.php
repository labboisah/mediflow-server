@extends('layouts.modern')

@section('title', 'User: ' . $user->name)
@section('page-title', 'User Details')
@section('page-subtitle', 'Review account status, department, and assigned roles.')

@section('content')
    <x-ui.page title="User Details" subtitle="Review account status, department, and assigned roles.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}">
                <x-ui.button type="button" variant="secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </x-ui.button>
            </a>
        </x-slot:actions>

        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-8" title="{{ $user->name }}" subtitle="{{ $user->email }}">
                <dl class="grid gap-5 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-med-muted">Full Name</dt>
                        <dd class="mt-2 text-xl font-semibold text-med-ink">{{ $user->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-med-muted">Email</dt>
                        <dd class="mt-2 text-xl font-semibold text-med-ink">{{ $user->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-med-muted">Department</dt>
                        <dd class="mt-2 text-xl font-semibold text-med-ink">{{ $user->department->name ?? 'Not assigned' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-med-muted">Account Status</dt>
                        <dd class="mt-2">
                            @if($user->trashed())
                                <x-ui.badge variant="danger">Deleted</x-ui.badge>
                            @else
                                <x-ui.badge variant="success">Active</x-ui.badge>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-med-muted">Registered</dt>
                        <dd class="mt-2 text-base font-semibold text-med-ink">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                    </div>

                    @if($user->updated_at->ne($user->created_at))
                        <div>
                            <dt class="text-sm font-medium text-med-muted">Last Updated</dt>
                            <dd class="mt-2 text-base font-semibold text-med-ink">{{ $user->updated_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 flex flex-wrap gap-3 border-t border-med-line pt-5">
                    @if ($user->id !== auth()->id())
                        <a href="{{ route('admin.users.edit', $user->id) }}">
                            <x-ui.button type="button" variant="secondary">
                                <i class="bi bi-pencil"></i>
                                Edit User
                            </x-ui.button>
                        </a>

                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger">
                                <i class="bi bi-trash"></i>
                                Delete User
                            </x-ui.button>
                        </form>
                    @else
                        <span class="text-base text-med-muted">This is your account</span>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="xl:col-span-4" title="Assigned Roles">
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
