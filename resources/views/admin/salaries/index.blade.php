@extends('layouts.modern')

@section('page-title', 'Salary Account')

@section('content')
@php
    $selectedMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('M Y');
@endphp

<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Salary Paid for {{ $selectedMonthLabel }}</p>
        <p class="text-3xl font-bold text-primary mt-2">NGN {{ number_format($monthPaid, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">Salary Paid This Year</p>
        <p class="text-3xl font-bold text-accent mt-2">NGN {{ number_format($yearPaid, 2) }}</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow">
        <p class="text-sm text-gray-500">All-Time Salary Paid</p>
        <p class="text-3xl font-bold text-secondary mt-2">NGN {{ number_format($totalPaid, 2) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-2xl shadow lg:col-span-1">
        <h2 class="text-xl font-bold text-primary mb-5">Record Monthly Salary</h2>

        <form method="GET" action="{{ route('admin.salaries.index') }}" class="mb-6">
            <label class="block text-sm font-medium mb-2">View Month</label>
            <div class="flex gap-3">
                <input type="month" name="month" value="{{ $selectedMonth }}"
                       class="w-full px-4 py-3 border rounded-xl">
                <button class="bg-primary text-white px-5 py-3 rounded-xl">View</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.salaries.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-2">Salary Month</label>
                <input type="month" name="salary_month" value="{{ old('salary_month', $selectedMonth) }}"
                       class="w-full px-4 py-3 border rounded-xl">
                @error('salary_month')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Total Amount Paid</label>
                <input type="number" step="0.01" name="amount"
                       value="{{ old('amount', $salaryPayment?->amount ?? 0) }}"
                       class="w-full px-4 py-3 border rounded-xl">
                @error('amount')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Note</label>
                <textarea name="note" rows="4" class="w-full px-4 py-3 border rounded-xl">{{ old('note', $salaryPayment?->note) }}</textarea>
                @error('note')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <button class="bg-accent text-white px-6 py-3 rounded-xl">
                Save Salary
            </button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow lg:col-span-2 overflow-x-auto">
        <h2 class="text-xl font-bold text-primary mb-5">Salary Records</h2>

        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Month</th>
                    <th class="py-3 px-4 text-left">Amount</th>
                    <th class="py-3 px-4 text-left">Recorded By</th>
                    <th class="py-3 px-4 text-left">Note</th>
                    <th class="py-3 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryPayments as $payment)
                    <tr class="border-b">
                        <td class="py-3 px-4">{{ $payment->salary_month->format('M Y') }}</td>
                        <td class="py-3 px-4 font-semibold text-accent">NGN {{ number_format($payment->amount, 2) }}</td>
                        <td class="py-3 px-4">{{ optional($payment->user)->name ?? 'System' }}</td>
                        <td class="py-3 px-4">{{ $payment->note }}</td>
                        <td class="py-3 px-4 text-right">
                            <form method="POST" action="{{ route('admin.salaries.destroy', $payment) }}"
                                  onsubmit="return confirm('Delete this salary record?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-6 text-center text-gray-500" colspan="5">
                            No salary has been recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-6">
            {{ $salaryPayments->links() }}
        </div>
    </div>
</div>
@endsection

