@extends('layouts.modern')

@section('title', 'Laboratory Requests')

@section('content')
    <x-ui.page title="Laboratory Requests" subtitle="Grouped investigation requests awaiting payment, result entry, or review.">
        <x-slot:actions><a href="{{ route('dashboard') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Dashboard</a></x-slot:actions>
        <x-ui.card title="Request Queue" subtitle="{{ $requestGroups->count() }} grouped request{{ $requestGroups->count() === 1 ? '' : 's' }} found.">
            <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Lab No</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Investigations</th><th class="px-4 py-3">Requested By</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Requested At</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @forelse($requestGroups as $group)
                    <tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $group->lab_no }}</td><td class="px-4 py-4 text-med-muted">{{ $group->patient_name }}</td><td class="px-4 py-4 text-med-muted">{{ $group->investigations }}</td><td class="px-4 py-4 text-med-muted">{{ $group->requested_by }}</td><td class="px-4 py-4">{{ $group->payment_status === 'Paid' ? '' : '' }}<x-ui.badge variant="{{ $group->payment_status === 'Paid' ? 'success' : 'danger' }}">{{ $group->payment_status }}</x-ui.badge></td><td class="px-4 py-4"><x-ui.badge variant="{{ $group->status === 'Completed' ? 'success' : 'warning' }}">{{ $group->status }}</x-ui.badge></td><td class="px-4 py-4 text-med-muted">{{ $group->requested_at?->format('M d, Y h:i A') }}</td><td class="px-4 py-4 text-right">@if($group->group_id && $group->has_pending_results)<a href="{{ route('lab.requests.results.create', ['groupType' => $group->group_type, 'groupId' => $group->group_id]) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Send Result</a>@elseif($group->has_completed_results && $group->bill_id)<a href="{{ route('lab.requests.show', $group->bill_id) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">View Results</a>@else<span class="text-sm text-med-muted">{{ $group->payment_status }}</span>@endif</td></tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8"><x-ui.empty-state title="No Investigation Requests" message="Laboratory requests will appear here after clinicians request investigations." /></td></tr>
                @endforelse
            </tbody></x-ui.table>
        </x-ui.card>
    </x-ui.page>
@endsection
