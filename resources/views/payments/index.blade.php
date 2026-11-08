@extends('layouts.modern')

@section('page-title', 'Payments')

@section('content')

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-xl font-bold text-primary">
        Payment Ledger
    </h2>
</div>

<div class="bg-white rounded-2xl shadow p-6 overflow-x-auto">

    <table class="w-full text-sm text-left">

        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4">Bill No</th>
                <th class="px-4">Patient</th>
                <th class="px-4">Amount (₦)</th>
                <th class="px-4">Method</th>
                <th class="px-4">Recorded By</th>
                <th class="px-4">Date</th>
                <th class="px-4 text-right">Action</th>
            </tr>
        </thead>

        <tbody>

        @forelse($payments as $payment)
            <tr class="border-b hover:bg-lightbg transition">

                <td class="py-3 px-4 font-semibold text-primary">
                    {{ $payment->bill->bill_no }}
                </td>

                <td class="px-4">
                    {{ $payment->bill->patient_name ?? 'Walk-in' }}
                </td>

                <td class="px-4 font-semibold text-accent">
                    ₦{{ number_format($payment->amount, 2) }}
                </td>

                <td class="px-4">
                    <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                        {{ $payment->payment_method ?? 'Cash' }}
                    </span>
                </td>

                <td class="px-4">
                    {{ $payment->user->name ?? '-' }}
                </td>

                <td class="px-4 text-gray-500">
                    {{ $payment->created_at->format('d M Y h:i A') }}
                </td>

                <td class="px-4 text-right">
                    <a href="{{ route('bills.show', $payment->bill_id) }}"
                       class="text-blue-600 hover:underline">
                        View Bill
                    </a>
                    <a href="{{ route('payments.edit', $payment) }}"
                    class="text-blue-600 hover:underline mr-3">
                        Edit
                    </a>
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="7" class="text-center py-6 text-gray-500">
                    No payments recorded yet.
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

    <div class="mt-6">
        {{ $payments->links() }}
    </div>

</div>

@endsection
