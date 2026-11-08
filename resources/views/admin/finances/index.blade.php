@extends('layouts.modern')

@section('page-title', 'Financial Summary')

@section('content')
@php
    $selectedMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('M Y');
@endphp

<div class="grid md:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Revenue Today</p>
        <p class="text-2xl font-bold text-primary mt-2">
            NGN {{ number_format($report['todayRevenue'],2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Cash Collected Today</p>
        <p class="text-2xl font-bold text-accent mt-2">
            NGN {{ number_format($report['todayPayments'],2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Expenses Today</p>
        <p class="text-2xl font-bold text-red-600 mt-2">
            NGN {{ number_format($report['todayExpenses'],2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Net Profit Today</p>
        <p class="text-2xl font-bold {{ $report['todayProfit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
            NGN {{ number_format($report['todayProfit'],2) }}
        </p>
    </div>
</div>

<div class="grid md:grid-cols-5 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Outstanding Bills Today</p>
        <p class="text-2xl font-bold text-yellow-600 mt-2">
            NGN {{ number_format($report['todayOutstanding'],2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Revenue for {{ $selectedMonthLabel }}</p>
        <p class="text-2xl font-bold text-primary mt-2">
            NGN {{ number_format($monthNetRevenue,2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Expenses for {{ $selectedMonthLabel }}</p>
        <p class="text-2xl font-bold text-red-600 mt-2">
            NGN {{ number_format($monthExpenses,2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Salary Paid for {{ $selectedMonthLabel }}</p>
        <p class="text-2xl font-bold text-yellow-600 mt-2">
            NGN {{ number_format($monthSalaryPaid,2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Net Profit for {{ $selectedMonthLabel }}</p>
        <p class="text-2xl font-bold {{ $monthProfit >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
            NGN {{ number_format($monthProfit,2) }}
        </p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-10">
    <div class="bg-white p-6 rounded-2xl shadow lg:col-span-1">
        <h3 class="text-lg font-bold text-primary mb-4">Record Monthly Salary</h3>

        <form method="GET" action="{{ route('admin.finances.index') }}" class="mb-6">
            <label class="block text-sm font-medium mb-2">View Month</label>
            <div class="flex gap-3">
                <input type="month" name="month" value="{{ $selectedMonth }}"
                       class="w-full px-4 py-3 border rounded-xl">
                <button class="bg-primary text-white px-5 py-3 rounded-xl">View</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.finances.store') }}" class="space-y-4">
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
                <textarea name="note" rows="3" class="w-full px-4 py-3 border rounded-xl">{{ old('note', $salaryPayment?->note) }}</textarea>
                @error('note')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>

            <button class="bg-accent text-white px-6 py-3 rounded-xl">
                Save Salary
            </button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow lg:col-span-2 overflow-x-auto">
        <h3 class="text-lg font-bold text-primary mb-4">Salary Payment History</h3>

        <table class="w-full text-sm">
            <thead class="border-b bg-lightbg">
                <tr>
                    <th class="py-3 px-4 text-left">Month</th>
                    <th class="py-3 px-4 text-left">Amount</th>
                    <th class="py-3 px-4 text-left">Recorded By</th>
                    <th class="py-3 px-4 text-left">Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryPayments as $payment)
                    <tr class="border-b">
                        <td class="py-3 px-4">{{ $payment->salary_month->format('M Y') }}</td>
                        <td class="py-3 px-4 font-semibold text-yellow-600">NGN {{ number_format($payment->amount,2) }}</td>
                        <td class="py-3 px-4">{{ $payment->user->name }}</td>
                        <td class="py-3 px-4">{{ $payment->note }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="py-6 px-4 text-center text-gray-500" colspan="4">
                            No salary payment has been recorded yet.
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

<div class="bg-white rounded-2xl shadow mb-8 overflow-hidden">
    <button onclick="toggleSection('todaySection', 'todayIcon')"
        class="w-full flex justify-between items-center px-6 py-4 text-left">

        <h3 class="text-lg font-bold text-primary">
            View {{ date('d-M-Y') }}'s Financial Summary in Chart
        </h3>

        <svg id="todayIcon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div id="todaySection" class="hidden px-6 pb-6 transition-all duration-500 overflow-hidden">
        <canvas id="todayChart"></canvas>
    </div>
</div>

<div class="bg-white rounded-2xl shadow mb-8 overflow-hidden">
    <button onclick="toggleSection('monthSection', 'monthIcon')"
        class="w-full flex justify-between items-center px-6 py-4 text-left">

        <h3 class="text-lg font-bold text-primary">
            View {{ $selectedMonthLabel }}'s Financial Summary in Chart
        </h3>

        <svg id="monthIcon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="monthSection" class="hidden px-6 pb-6 transition-all duration-500 overflow-hidden">
        <canvas id="monthChart"></canvas>
    </div>
</div>
@endsection

@section('scripts')
<script>
const todayChart = new Chart(document.getElementById('todayChart'), {
    type: 'bar',
    data: {
        labels: ['Gross Revenue','Discount','Staff Share','Annex Share','Expenses','Profit'],
        datasets: [{
            label: 'Today',
            data: [
                {{ $todayGross }},
                {{ $todayDiscount }},
                {{ $todayStaffShare }},
                {{ $todayAnnexShare }},
                {{ $todayExpenses }},
                {{ $todayProfit }}
            ],
            backgroundColor: [
                '#1E4E8C',
                '#F59E0B',
                '#16A34A',
                '#0F2D5C',
                '#DC2626',
                '#10B981'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});

const monthChart = new Chart(document.getElementById('monthChart'), {
    type: 'bar',
    data: {
        labels: ['Gross Revenue','Discount','Staff Share','Annex Share','Expenses','Salary','Profit'],
        datasets: [{
            label: '{{ $selectedMonthLabel }}',
            data: [
                {{ $monthGross }},
                {{ $monthDiscount }},
                {{ $monthStaffShare }},
                {{ $monthAnnexShare }},
                {{ $monthExpenses }},
                {{ $monthSalaryPaid }},
                {{ $monthProfit }}
            ],
            backgroundColor: [
                '#1E4E8C',
                '#F59E0B',
                '#16A34A',
                '#0F2D5C',
                '#DC2626',
                '#D97706',
                '#10B981'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});
</script>
<script>
function toggleSection(sectionId, iconId) {
    const section = document.getElementById(sectionId);
    const icon = document.getElementById(iconId);

    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        icon.classList.remove('rotate-180');
    } else {
        section.classList.add('hidden');
        icon.classList.add('rotate-180');
    }
}
</script>
@endsection

