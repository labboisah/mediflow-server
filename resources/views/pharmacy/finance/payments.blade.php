@extends('layouts.modern')

@section('title', 'Pharmacy Payments')

@section('content')
    <x-ui.page title="Pharmacy Payments" subtitle="Payments collected from pharmacy transactions.">
        <x-slot:actions><a href="{{ route('pharmacy.transactions.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>New Transaction</a></x-slot:actions>
        <x-ui.card title="Payments" subtitle="{{ $transactions->total() }} payment transaction{{ $transactions->total() === 1 ? '' : 's' }} found.">
            <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Bill No</th><th class="px-4 py-3">Medicines / Services</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Collected By</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @forelse($transactions as $transaction)
                    @php($payment = $transaction->payment)
                    <tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $payment?->payment_id ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $transaction->bill?->bill_number ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">@foreach($transaction->stockTransactionItems as $item)<div>{{ $item->medicineBatch?->medicine?->name ?? 'N/A' }} <span>x {{ $item->quantity }}</span></div>@endforeach @foreach($transaction->serviceItems as $item)<div>{{ $item->name }} x {{ $item->quantity }} (Service)</div>@endforeach</td><td class="px-4 py-4 text-med-muted">{{ $payment?->paymentMethod?->name ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $transaction->createdBy?->name ?? 'System' }}</td><td class="px-4 py-4 text-med-muted">{{ $payment?->payment_date?->format('M d, Y h:i A') ?? $transaction->created_at?->format('M d, Y h:i A') }}</td><td class="px-4 py-4 text-right font-semibold text-med-ink">&#8358;{{ number_format($payment?->amount ?? $transaction->total_amount, 2) }}</td><td class="px-4 py-4 text-right">@if($payment)<a href="{{ route('pharmacy.finance.payments.receipt', $payment) }}" class="text-sm font-semibold text-med-primary hover:text-med-primaryDark">Receipt</a>@endif</td></tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8"><x-ui.empty-state title="No Pharmacy Payments" message="Payments collected from pharmacy transactions will appear here." /></td></tr>
                @endforelse
            </tbody></x-ui.table>
            @if($transactions->hasPages())<div class="mt-5">{{ $transactions->links() }}</div>@endif
        </x-ui.card>
    </x-ui.page>
@endsection
