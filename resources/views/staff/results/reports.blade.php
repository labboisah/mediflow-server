@extends('layouts.modern')

@section('page-title', 'Staff Reports')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow mb-6">
    <h2 class="text-xl font-bold text-primary mb-4">Search Reports</h2>

    <form method="GET" action="{{ route('staff.results.reports') }}" class="grid md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-2">From Date</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full px-4 py-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">To Date</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full px-4 py-3 border rounded-xl">
        </div>

        <div class="flex gap-3">
            <button class="bg-primary text-white px-6 py-3 rounded-xl">Search</button>
            <a href="{{ route('staff.results.reports') }}" class="bg-gray-200 text-primary px-6 py-3 rounded-xl">Reset</a>
        </div>

        @error('from')<p class="text-red-600 text-sm md:col-span-3">{{ $message }}</p>@enderror
        @error('to')<p class="text-red-600 text-sm md:col-span-3">{{ $message }}</p>@enderror
    </form>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
    <h2 class="text-xl font-bold text-primary mb-6">Entered Results</h2>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Bill No</th>
                <th class="py-3 px-4 text-left">Patient</th>
                <th class="py-3 px-4 text-left">Investigation</th>
                <th class="py-3 px-4 text-left">Performed By</th>
                <th class="py-3 px-4 text-left">Reported By</th>
                <th class="py-3 px-4 text-left">Activity Date</th>
                <th class="py-3 px-4 text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $result)
                <tr class="border-b">
                    <td class="py-3 px-4">{{ $result->bill->bill_no }}</td>
                    <td class="py-3 px-4">{{ $result->bill->patient_name ?? 'Walk-in' }}</td>
                    <td class="py-3 px-4">{{ $result->billItem->service->name }}</td>
                    <td class="py-3 px-4">{{ optional($result->performer)->name ?? '-' }}</td>
                    <td class="py-3 px-4">{{ optional($result->reporter)->name ?? optional($result->staff)->name ?? '-' }}</td>
                    <td class="py-3 px-4">{{ optional($staffType === 'radiographer' ? $result->performed_at : ($result->reported_at ?? $result->completed_at))->format('d M Y h:i A') }}</td>
                    <td class="py-3 px-4 text-right">
                        @if($result->reported_by || $result->staff_id)
                            <a href="{{ route('staff.results.print', $result) }}" class="text-primary hover:underline">Print</a>
                        @else
                            <span class="text-gray-500">Performed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-gray-500">No result has been entered yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        {{ $results->links() }}
    </div>
</div>
@endsection

