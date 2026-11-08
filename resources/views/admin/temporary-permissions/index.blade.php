@extends('layouts.modern')

@section('title', 'Temporary Permissions')
@section('page-title', 'Temporary Permissions')
@section('page-subtitle', 'Review temporary grants, expiry status, and revocation history.')

@section('content')
    <x-ui.page title="Temporary Permissions" subtitle="Review temporary grants, expiry status, and revocation history.">
        <x-slot:actions>
            <a href="{{ route('admin.temporary-permissions.create') }}">
                <x-ui.button>
                    <i class="bi bi-plus-circle"></i>
                    Grant Permission
                </x-ui.button>
            </a>
        </x-slot:actions>

        <x-ui.table>
            <thead class="bg-med-canvas">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">User</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Permission</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Granted By</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Reason</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Expires</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Status</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-med-line">
                @forelse ($tempPermissions as $tempPerm)
                    <tr class="hover:bg-med-canvas">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <span class="mf-icon-box">
                                    <i class="bi bi-person"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-med-ink">{{ $tempPerm->user->name }}</p>
                                    <p class="text-sm text-med-muted">{{ $tempPerm->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4"><x-ui.badge variant="success">{{ $tempPerm->permission->name }}</x-ui.badge></td>
                        <td class="px-4 py-4 text-sm text-med-muted">{{ $tempPerm->grantedBy?->name ?? '-' }}</td>
                        <td class="px-4 py-4 text-sm text-med-muted">{{ $tempPerm->reason ?? '-' }}</td>
                        <td class="px-4 py-4">
                            <p class="text-sm font-medium text-med-ink">{{ $tempPerm->expires_at->format('M d, Y H:i') }}</p>
                            <p class="mt-1 text-xs text-med-muted">{{ $tempPerm->isValid() ? $tempPerm->expires_at->diffForHumans() : 'Expired' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @if($tempPerm->is_active && $tempPerm->isValid())
                                <x-ui.badge variant="success">Active</x-ui.badge>
                            @else
                                <x-ui.badge>Inactive</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex justify-end gap-2">
                                @if($tempPerm->is_active && $tempPerm->isValid())
                                    <form action="{{ route('admin.temporary-permissions.revoke', $tempPerm->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-orange-700 hover:bg-med-canvas" title="Revoke now">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.temporary-permissions.destroy', $tempPerm->id) }}" method="POST" onsubmit="return confirm('Delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-med-line bg-white text-med-danger hover:bg-med-canvas" title="Delete record">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8">
                            <x-ui.empty-state title="No temporary permissions found" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div>
            {{ $tempPermissions->links() }}
        </div>
    </x-ui.page>
@endsection
