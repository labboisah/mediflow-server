@extends('layouts.modern')

@section('page-title', 'Bill Details')

@section('content')

<div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow">

    <div class="flex justify-between mb-6">
        <h2 class="text-2xl font-bold text-primary">
            Bill: {{ $bill->bill_no }}
        </h2>

        <a href="{{ route('bills.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back
        </a>
        @if($bill->balance > 0)
            <a href="{{ route('payments.create', $bill) }}"
            class="bg-accent text-white px-6 py-3 rounded-xl">
                Record Payment
            </a>
        @endif

        @if(($bill->results_count ?? 0) === 0)
            <form method="POST"
                  action="{{ route('bills.destroy', $bill) }}"
                  onsubmit="return confirm('Delete this bill?')">
                @csrf
                @method('DELETE')
                <button class="bg-red-600 text-white px-6 py-3 rounded-xl">
                    Delete Bill
                </button>
            </form>
        @endif
    </div>

    <div class="mb-6 text-sm text-gray-600">
        <p><strong>Patient:</strong> {{ $bill->patient_name ?? 'Walk-in' }}</p>
        <p><strong>Recorded By:</strong> {{ $bill->user->name }}</p>
        <p><strong>Date:</strong> {{ $bill->created_at->format('d M Y h:i A') }}</p>
    </div>

    <table class="w-full text-sm mb-6">

        <thead class="border-b">
            <tr>
                <th class="py-2">Service</th>
                <th>Price</th>
                <th>Staff Share</th>
                <th>Annex Share</th>
            </tr>
        </thead>

        <tbody>
        @foreach($bill->items as $item)
            <tr class="border-b">
                <td class="py-2">
                    {{ $item->service->name }}
                </td>
                <td>
                    ₦{{ number_format($item->price,2) }}
                </td>
                <td class="text-accent">
                    ₦{{ number_format($item->staff_share,2) }}
                </td>
                <td>
                    ₦{{ number_format($item->annex_share,2) }}
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

    <div class="text-right space-y-2">
        <p><strong>Total:</strong> ₦{{ number_format($bill->total_amount,2) }}</p>
        <p class="text-accent">
            <strong>Total Staff Share:</strong> ₦{{ number_format($bill->total_staff_share,2) }}
        </p>
        <p>
            <strong>Total Annex Share:</strong> ₦{{ number_format($bill->total_annex_share,2) }}
        </p>
    </div>
    

</div>

@endsection

