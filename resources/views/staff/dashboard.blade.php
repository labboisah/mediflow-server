@extends('layouts.modern')

@section('page-title', 'Staff Dashboard')

@section('content')
<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Results Today</p>
        <p class="text-3xl font-bold text-primary mt-2">{{ $todayResults }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Results This Month</p>
        <p class="text-3xl font-bold text-secondary mt-2">{{ $monthResults }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Commission This Month</p>
        <p class="text-3xl font-bold text-accent mt-2">NGN {{ number_format($monthCommission, 2) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl shadow lg:col-span-1">
        <h3 class="font-bold text-primary mb-4">Start Result Entry</h3>
        <form method="POST" action="{{ route('staff.results.lookup') }}" class="space-y-4">
            @csrf
            <input type="text" name="bill_no" value="{{ old('bill_no') }}"
                   placeholder="Example: B260719-1234"
                   class="w-full px-4 py-3 border rounded-xl">
            @error('bill_no')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            <button class="bg-accent text-white px-6 py-3 rounded-xl w-full">Open Bill</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow lg:col-span-2 overflow-x-auto">
        <h3 class="font-bold text-primary mb-4">Recent Results</h3>
        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Bill No</th>
                    <th class="py-3 px-4 text-left">Investigation</th>
                    <th class="py-3 px-4 text-left">Activity Date</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentResults as $result)
                    <tr class="border-b">
                        <td class="py-3 px-4">{{ $result->bill->bill_no }}</td>
                        <td class="py-3 px-4">{{ $result->billItem->service->name }}</td>
                        <td class="py-3 px-4">{{ optional($result->performed_at ?? $result->reported_at ?? $result->completed_at)->format('d M Y h:i A') }}</td>
                        <td class="py-3 px-4 text-right">
                            @if($result->reported_by)
                                <a href="{{ route('staff.results.print', $result) }}" class="text-primary hover:underline">Print</a>
                            @else
                                <span class="text-gray-500">Performed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500">No result has been entered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

