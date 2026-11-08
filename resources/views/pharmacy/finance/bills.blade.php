@extends('layouts.modern')

@section('title', 'Pharmacy Bills')

@section('content')
    <x-ui.page title="Pharmacy Bills" subtitle="Bills generated from pharmacy transactions.">
        <x-slot:actions><a href="{{ route('pharmacy.transactions.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>New Transaction</a></x-slot:actions>
        <x-ui.card title="Bills" subtitle="{{ $transactions->total() }} bill transaction{{ $transactions->total() === 1 ? '' : 's' }} found.">
            <x-ui.table class="shadow-none"><thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Bill No</th><th class="px-4 py-3">Medicines / Services</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Collected By</th><th class="px-4 py-3">Date</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Paid</th></tr></thead><tbody class="divide-y divide-med-line bg-white">
                @forelse($transactions as $transaction)
                    @php($bill = $transaction->bill)
                    <tr><td class="px-4 py-4 font-semibold text-med-ink">{{ $bill?->bill_number ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">@foreach($transaction->stockTransactionItems as $item)<div>{{ $item->medicineBatch?->medicine?->name ?? 'N/A' }} <span>x {{ $item->quantity }}</span></div>@endforeach @foreach($transaction->serviceItems as $item)<div>{{ $item->name }} x {{ $item->quantity }} (Service)</div>@endforeach</td><td class="px-4 py-4"><x-ui.badge variant="success">{{ ucfirst($bill?->status ?? 'paid') }}</x-ui.badge></td><td class="px-4 py-4 text-med-muted">{{ $transaction->createdBy?->name ?? 'System' }}</td><td class="px-4 py-4 text-med-muted">{{ $bill?->issued_date?->format('M d, Y h:i A') ?? $transaction->created_at?->format('M d, Y h:i A') }}</td><td class="px-4 py-4 text-right font-semibold text-med-ink">&#8358;{{ number_format($bill?->due_amount ?? $transaction->total_amount, 2) }}</td><td class="px-4 py-4 text-right font-semibold text-med-ink">&#8358;{{ number_format($bill?->totalPaid() ?? $transaction->payment?->amount ?? 0, 2) }}</td></tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8"><x-ui.empty-state title="No Pharmacy Bills" message="Bills generated from pharmacy transactions will appear here." /></td></tr>
                @endforelse
            </tbody></x-ui.table>
            @if($transactions->hasPages())<div class="mt-5">{{ $transactions->links() }}</div>@endif
        </x-ui.card>
    </x-ui.page>
@endsection
