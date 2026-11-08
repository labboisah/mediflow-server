@extends('layouts.modern')

@section('page-title', 'Commission')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow mb-6">
    <div class="flex flex-wrap gap-3 justify-between mb-4">
        <h2 class="text-xl font-bold text-primary">Search Commission</h2>
        <span class="px-3 py-1 rounded-full bg-lightbg text-secondary text-xs font-semibold uppercase">
            {{ $staffType }} share
        </span>
    </div>

    <form method="GET" action="{{ route('staff.results.commission') }}" class="grid md:grid-cols-3 gap-4 items-end">
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
            <a href="{{ route('staff.results.commission') }}" class="bg-gray-200 text-primary px-6 py-3 rounded-xl">Reset</a>
        </div>

        @error('from')<p class="text-red-600 text-sm md:col-span-3">{{ $message }}</p>@enderror
        @error('to')<p class="text-red-600 text-sm md:col-span-3">{{ $message }}</p>@enderror
    </form>
</div>

<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Commission Today</p>
        <p class="text-3xl font-bold text-accent mt-2">NGN {{ number_format($todayCommission, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Commission This Month</p>
        <p class="text-3xl font-bold text-primary mt-2">NGN {{ number_format($monthCommission, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Commission In Search</p>
        <p class="text-3xl font-bold text-secondary mt-2">NGN {{ number_format($filteredCommission, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Bill Amount In Search</p>
        <p class="text-3xl font-bold text-primary mt-2">NGN {{ number_format($filteredBillAmount, 2) }}</p>
    </div>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
    <h2 class="text-xl font-bold text-primary mb-6">Commission Ledger</h2>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Bill No</th>
                <th class="py-3 px-4 text-left">Investigation</th>
                <th class="py-3 px-4 text-right">Bill Amount</th>
                <th class="py-3 px-4 text-left">Activity Date</th>
                <th class="py-3 px-4 text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $result)
                <tr class="border-b">
                    <td class="py-3 px-4">{{ $result->bill->bill_no }}</td>
                    <td class="py-3 px-4">{{ $result->billItem->service->name }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($result->bill_amount ?? $result->billItem->price ?? 0, 2) }}</td>
                    <td class="py-3 px-4">{{ optional($staffType === 'radiographer' ? $result->performed_at : ($result->reported_at ?? $result->completed_at))->format('d M Y h:i A') }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-accent">
                        NGN {{ number_format($result->commission_amount ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500">No commission has been earned yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        {{ $results->links() }}
    </div>
</div>
@endsection

