@extends('layouts.modern')

@section('page-title', 'Edit Payment')

@section('content')

<div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-primary">
            Edit Payment
        </h2>

        <a href="{{ route('payments.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back to Payments
        </a>
    </div>

    <!-- BILL INFO -->
    <div class="mb-6 text-sm text-gray-600">
        <p><strong>Bill No:</strong> {{ $payment->bill->bill_no }}</p>
        <p><strong>Patient:</strong> {{ $payment->bill->patient_name ?? 'Walk-in' }}</p>
        <p><strong>Current Balance:</strong> 
            ₦{{ number_format($payment->bill->balance,2) }}
        </p>
    </div>

    <form method="POST" action="{{ route('payments.update', $payment) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm mb-2">Amount</label>
            <input type="number" step="0.01"
                   name="amount"
                   value="{{ old('amount', $payment->amount) }}"
                   class="w-full px-4 py-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm mb-2">Payment Method</label>
            <select name="payment_method"
                    class="w-full px-4 py-3 border rounded-xl">
                <option value="Cash" 
                    {{ $payment->payment_method == 'Cash' ? 'selected' : '' }}>
                    Cash
                </option>
                <option value="Transfer"
                    {{ $payment->payment_method == 'Transfer' ? 'selected' : '' }}>
                    Transfer
                </option>
                <option value="POS"
                    {{ $payment->payment_method == 'POS' ? 'selected' : '' }}>
                    POS
                </option>
            </select>
        </div>

        <div>
            <label class="block text-sm mb-2">Note</label>
            <textarea name="note"
                class="w-full px-4 py-3 border rounded-xl">{{ $payment->note }}</textarea>
        </div>

        <button class="bg-secondary text-white px-8 py-3 rounded-xl">
            Update Payment
        </button>

    </form>

</div>

@endsection
