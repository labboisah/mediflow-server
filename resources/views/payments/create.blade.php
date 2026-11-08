@extends('layouts.modern')

@section('page-title', 'Record Payment')

@section('content')

<div class="max-w-4xl mx-auto space-y-8">

    <!-- BILL SUMMARY -->
    <div class="bg-white p-8 rounded-2xl shadow">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-primary">
                Bill: {{ $bill->bill_no }}
            </h2>

            <a href="{{ route('bills.show', $bill) }}"
               class="text-sm text-primary hover:underline">
                ← Back to Bill
            </a>
        </div>

        <div class="grid md:grid-cols-3 gap-6 text-sm">

            <div>
                <p class="text-gray-500">Final Amount</p>
                <p class="text-lg font-bold">
                    ₦{{ number_format($bill->final_amount,2) }}
                </p>
            </div>

            <div>
                <p class="text-gray-500">Total Paid</p>
                <p class="text-lg font-bold text-accent">
                    ₦{{ number_format($bill->total_paid,2) }}
                </p>
            </div>

            <div>
                <p class="text-gray-500">Balance</p>
                <p class="text-lg font-bold text-red-600">
                    ₦{{ number_format($bill->balance,2) }}
                </p>
            </div>

        </div>

        <div class="mt-4">
            <span class="px-4 py-1 rounded-full text-xs font-semibold
                {{ $bill->payment_status === 'paid' ? 'bg-green-100 text-green-700' :
                   ($bill->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-700' :
                   'bg-red-100 text-red-700') }}">
                {{ strtoupper($bill->payment_status) }}
            </span>
        </div>

    </div>

    <!-- ADD PAYMENT -->
    @if($bill->balance > 0)

    <div class="bg-white p-8 rounded-2xl shadow">

        <h3 class="text-lg font-bold mb-6">Add Payment</h3>

        <form method="POST" action="{{ route('payments.store') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="bill_id" value="{{ $bill->id }}">

            <div>
                <label class="block text-sm mb-2">Amount</label>
                <input type="number" step="0.01" name="amount" value="{{$bill->balance}}"
                       class="w-full px-4 py-3 border rounded-xl">
            </div>

            <div>
                <label class="block text-sm mb-2">Payment Method</label>
                <select name="payment_method"
                        class="w-full px-4 py-3 border rounded-xl">
                    <option value="Cash">Cash</option>
                    <option value="Transfer">Bank Transfer</option>
                    <option value="POS">POS</option>
                </select>
            </div>

            <div>
                <label class="block text-sm mb-2">Note (Optional)</label>
                <textarea name="note"
                          class="w-full px-4 py-3 border rounded-xl"></textarea>
            </div>

            <button class="bg-accent text-white px-8 py-3 rounded-xl">
                Record Payment
            </button>

        </form>

    </div>

    @endif

    <!-- PAYMENT HISTORY -->
    <div class="bg-white p-8 rounded-2xl shadow">

        <h3 class="text-lg font-bold mb-6">Payment History</h3>

        @forelse($bill->payments as $payment)
            <div class="flex justify-between border-b py-3 text-sm">
                <div>
                    <strong>{{ $payment->payment_method }}</strong>
                    <br>
                    <small class="text-gray-500">
                        {{ $payment->created_at->format('d M Y h:i A') }}
                    </small>
                </div>
                <div class="font-bold text-primary">
                    ₦{{ number_format($payment->amount,2) }}
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-sm">
                No payments recorded yet.
            </p>
        @endforelse

    </div>

</div>

@endsection
