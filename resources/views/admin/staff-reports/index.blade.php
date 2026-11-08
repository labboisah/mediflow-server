@extends('layouts.modern')

@section('page-title', 'Staff Reports')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow mb-6">
    <div class="flex flex-wrap gap-3 justify-between mb-5">
        <h2 class="text-xl font-bold text-primary">Staff Work Report</h2>

        <a href="{{ route('admin.staff-reports.download', request()->query()) }}"
           class="bg-accent text-white px-5 py-3 rounded-xl">
            Download Summary
        </a>
    </div>

    <form method="GET" action="{{ route('admin.staff-reports.index') }}" class="grid md:grid-cols-4 gap-4 items-end">
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

        <div>
            <label class="block text-sm font-medium mb-2">Staff</label>
            <select name="staff_id" class="w-full px-4 py-3 border rounded-xl">
                <option value="">All staff</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff->id }}" {{ (string) $staffId === (string) $staff->id ? 'selected' : '' }}>
                        {{ $staff->name }} ({{ strtoupper($staff->staff_type ?? 'staff') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3">
            <button class="bg-primary text-white px-6 py-3 rounded-xl">Search</button>
            <a href="{{ route('admin.staff-reports.index') }}" class="bg-gray-200 text-primary px-6 py-3 rounded-xl">Reset</a>
        </div>

        @error('from')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
        @error('to')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
        @error('staff_id')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
    </form>
</div>

<div class="grid md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Total Results</p>
        <p class="text-3xl font-bold text-primary mt-2">{{ $totalResults }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Total Commission</p>
        <p class="text-3xl font-bold text-accent mt-2">NGN {{ number_format($totalCommission, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Total Bill Amount</p>
        <p class="text-3xl font-bold text-secondary mt-2">NGN {{ number_format($totalBillAmount, 2) }}</p>
    </div>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto mb-6">
    <h3 class="text-lg font-bold text-primary mb-4">Summary By Staff</h3>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Staff Name</th>
                <th class="py-3 px-4 text-left">Staff Type</th>
                <th class="py-3 px-4 text-left">Total Results</th>
                <th class="py-3 px-4 text-right">Bill Amount</th>
                <th class="py-3 px-4 text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary as $row)
                <tr class="border-b">
                    <td class="py-3 px-4 font-semibold">{{ $row['staff_name'] }}</td>
                    <td class="py-3 px-4">{{ $row['staff_type'] }}</td>
                    <td class="py-3 px-4">{{ $row['total_results'] }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-primary">
                        NGN {{ number_format($row['total_bill_amount'], 2) }}
                    </td>
                    <td class="py-3 px-4 text-right font-semibold text-accent">
                        NGN {{ number_format($row['total_commission'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500">No staff work found for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
    <h3 class="text-lg font-bold text-primary mb-4">Detailed Work</h3>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Performed By</th>
                <th class="py-3 px-4 text-left">Reported By</th>
                <th class="py-3 px-4 text-left">Bill No</th>
                <th class="py-3 px-4 text-left">Patient</th>
                <th class="py-3 px-4 text-left">Investigation</th>
                <th class="py-3 px-4 text-right">Bill Amount</th>
                <th class="py-3 px-4 text-left">Activity Date</th>
                <th class="py-3 px-4 text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $result)
                <tr class="border-b">
                    <td class="py-3 px-4">{{ optional($result->performer)->name ?? '-' }}</td>
                    <td class="py-3 px-4">{{ optional($result->reporter)->name ?? optional($result->staff)->name ?? '-' }}</td>
                    <td class="py-3 px-4">{{ $result->bill->bill_no }}</td>
                    <td class="py-3 px-4">{{ $result->bill->patient_name ?? 'Walk-in' }}</td>
                    <td class="py-3 px-4">{{ $result->billItem->service->name }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($result->bill_amount ?? $result->billItem->price ?? 0, 2) }}</td>
                    <td class="py-3 px-4">
                        {{ optional($result->reported_at ?? $result->performed_at ?? $result->completed_at)->format('d M Y h:i A') }}
                    </td>
                    <td class="py-3 px-4 text-right font-semibold text-accent">
                        NGN {{ number_format($result->commission_amount ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-6 text-center text-gray-500">No staff work found for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        {{ $results->links() }}
    </div>
</div>
@endsection

