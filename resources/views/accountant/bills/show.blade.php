@extends('layouts.modern')

@section('title', 'Bill Details')
@section('page-title', 'Bill Details')
@section('page-subtitle', 'Review charges, payment status, and print the bill.')

@section('content')
@php
    if ($bill->walkinPatient) {
        $patientName = $bill->walkinPatient->name;
        $hospitalNumber = 'Walk-in Patient';
    } elseif ($bill->patientVisit) {
        $patientName = $bill->patientVisit->patient->demographic->getFullNameAttribute();
        $hospitalNumber = $bill->patientVisit->patient->hospital_number;
    } else {
        $patientName = 'N/A';
        $hospitalNumber = 'N/A';
    }

    $billDate = now()->format('M d, Y h:i A');
    $statusClasses = match ($bill->status) {
        'paid' => 'border-green-200 bg-green-50 text-green-700',
        'partial' => 'border-amber-200 bg-amber-50 text-amber-700',
        'pending' => 'border-red-200 bg-red-50 text-red-700',
        default => 'border-med-line bg-med-canvas text-med-muted',
    };
@endphp

<div class="mx-auto max-w-5xl space-y-6 bill-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bill-no-print">
        <div>
            <h1 class="text-2xl font-semibold text-med-ink">Bill details</h1>
            <p class="mt-1 text-sm text-med-muted">Bill #{{ $bill->bill_number }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($bill->status !== 'paid')
                <a href="{{ route('accountant.bills.payments.create', $bill) }}" class="mf-focus inline-flex items-center gap-2 rounded-md bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                    <i class="bi bi-cash-coin" aria-hidden="true"></i> Record payment
                </a>
            @endif
            @if($bill->canBeManagedAsUnpaidByAccountant(auth()->user()))
                <a href="{{ route('accountant.bills.edit', $bill) }}" class="mf-focus inline-flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit bill
                </a>
            @endif
            <a href="{{ route('accountant.bills.index') }}" class="mf-focus inline-flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to bills
            </a>
        </div>
    </div>

    <section class="rounded-xl border border-med-line bg-white p-4 shadow-panel sm:p-6 bill-no-print" aria-label="Bill printing options">
        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="button" onclick="window.print()" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md bg-med-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                <i class="bi bi-printer" aria-hidden="true"></i> Print A4 bill
            </button>
            <button type="button" onclick="printThermalBill()" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-4 py-2.5 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                <i class="bi bi-receipt" aria-hidden="true"></i> Print thermal bill
            </button>
        </div>
    </section>

    <section id="bill-preview" class="overflow-hidden rounded-xl border border-med-line bg-white shadow-panel">
        <header class="flex items-center justify-between gap-4 bg-med-primary px-5 py-4 text-white sm:px-6">
            <div>
                <h2 class="text-lg font-semibold">Bill statement</h2>
                <p class="mt-1 text-sm text-white/80">Bill #{{ $bill->bill_number }}</p>
            </div>
            <i class="bi bi-receipt-cutoff text-3xl" aria-hidden="true"></i>
        </header>

        <div class="space-y-6 p-4 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Patient</p><p class="mt-1 font-semibold text-med-ink">{{ $patientName }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Hospital number</p><p class="mt-1 font-semibold text-med-ink">{{ $hospitalNumber }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Bill status</p><span class="mt-1 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ ucfirst($bill->status) }}</span></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Discount</p><p class="mt-1 font-semibold text-med-ink">{{ number_format($bill->discount, 0) }}%</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Issued date</p><p class="mt-1 font-semibold text-med-ink">{{ $bill->issued_date->format('M d, Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Due date</p><p class="mt-1 font-semibold text-med-ink">{{ $bill->due_date->format('M d, Y') }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Issued by</p><p class="mt-1 font-semibold text-med-ink">{{ $bill->issuedBy->name }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-med-muted">Printed on</p><p class="mt-1 font-semibold text-med-ink">{{ $billDate }}</p></div>
            </div>

            @if($bill->services->count() > 0 || $bill->investigations->count() > 0)
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-med-ink">Services and items</h3>
                    <div class="overflow-x-auto rounded-lg border border-med-line">
                        <table class="min-w-full divide-y divide-med-line text-sm">
                            <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                                <tr><th class="px-4 py-3">Description</th><th class="px-4 py-3 text-right">Unit price</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Subtotal</th><th class="px-4 py-3 text-right">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-med-line">
                                @foreach($bill->services as $service)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-med-ink">{{ $service->name }}<span class="mt-0.5 block text-xs text-med-muted">{{ $service->code }}</span></td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ number_format($service->pivot->unit_price, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ $service->pivot->quantity }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-med-ink">{{ number_format($service->pivot->subtotal, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ ucfirst($bill->status) }}</td>
                                    </tr>
                                @endforeach
                                @foreach($bill->investigations as $investigation)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-med-ink">{{ $investigation->name }}<span class="mt-0.5 block text-xs text-med-muted">{{ $investigation->code }}</span></td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ number_format($investigation->pivot->unit_price, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ $investigation->pivot->quantity }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-med-ink">{{ number_format($investigation->pivot->subtotal, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-med-muted">{{ ucfirst($bill->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-med-canvas font-semibold text-med-ink">
                                <tr><td colspan="3" class="px-4 py-3 text-right">Total</td><td class="px-4 py-3 text-right">{{ number_format($bill->amount, 2) }}</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif

            <div class="grid gap-3 border-t border-med-line pt-5 sm:grid-cols-3">
                <div class="rounded-lg bg-med-canvas p-4"><p class="text-sm text-med-muted">Amount due</p><p class="mt-1 text-xl font-semibold text-med-ink">{{ number_format($bill->due_amount, 2) }}</p></div>
                <div class="rounded-lg bg-green-50 p-4"><p class="text-sm text-med-muted">Total paid</p><p class="mt-1 text-xl font-semibold text-med-success">{{ number_format($bill->totalPaid(), 2) }}</p></div>
                <div class="rounded-lg bg-red-50 p-4"><p class="text-sm text-med-muted">Balance due</p><p class="mt-1 text-xl font-semibold text-med-danger">{{ number_format($bill->balance, 2) }}</p></div>
            </div>

            <p class="border-t border-med-line pt-4 text-center text-xs text-med-muted">This bill is generated by the system. Please keep it for your records.</p>
        </div>
    </section>

    <section id="thermal-bill-preview-card" class="rounded-xl border border-med-line bg-white p-4 shadow-panel sm:p-6 bill-no-print">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-semibold text-med-ink">Thermal bill preview</h2><p class="mt-1 text-sm text-med-muted">Review before printing.</p></div>
            <button type="button" onclick="printThermalBill()" class="mf-focus inline-flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                <i class="bi bi-receipt" aria-hidden="true"></i> Print thermal
            </button>
        </div>
        <div class="mx-auto max-w-sm rounded-lg border border-dashed border-med-line bg-med-canvas p-4">
            <div class="thermal-bill-preview font-mono text-xs text-med-ink">
                <div class="mb-3 text-center">
                    <h3 class="text-base font-bold">{{ strtoupper(config('app.name', 'FAYHOS')) }}</h3>
                    <p class="mt-1">Bill statement</p>
                    <div class="thermal-divider"></div>
                </div>
                <dl class="space-y-1">
                    <div class="flex justify-between gap-2"><dt>Bill</dt><dd>{{ $bill->bill_number }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>Date</dt><dd>{{ $billDate }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>Patient</dt><dd class="text-right">{{ $patientName }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>Hospital No</dt><dd>{{ $hospitalNumber }}</dd></div>
                    <div class="flex justify-between gap-2"><dt>Status</dt><dd>{{ ucfirst($bill->status) }}</dd></div>
                </dl>
                <div class="thermal-divider"></div>
                <table class="w-full text-left">
                    <tbody>
                        @foreach($bill->services as $service)
                            <tr><td>{{ Str::limit($service->name, 12) }}</td><td class="text-right">{{ number_format($service->pivot->unit_price, 2) }}</td><td class="text-right">{{ $service->pivot->quantity }}</td><td class="text-right">{{ number_format($service->pivot->subtotal, 2) }}</td></tr>
                        @endforeach
                        @foreach($bill->investigations as $investigation)
                            <tr><td>{{ Str::limit($investigation->name, 12) }}</td><td class="text-right">{{ number_format($investigation->pivot->unit_price, 2) }}</td><td class="text-right">{{ $investigation->pivot->quantity }}</td><td class="text-right">{{ number_format($investigation->pivot->subtotal, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="thermal-divider"></div>
                <p class="flex justify-between"><strong>Total due</strong><strong>{{ number_format($bill->due_amount, 2) }}</strong></p>
                <p class="flex justify-between"><span>Total paid</span><span>{{ number_format($bill->totalPaid(), 2) }}</span></p>
                <p class="flex justify-between"><strong>Balance</strong><strong>{{ number_format($bill->balance, 2) }}</strong></p>
                <div class="thermal-divider"></div>
                <p>Issued by: {{ $bill->issuedBy->name }}</p>
                <p class="mt-1">Please settle payment promptly.</p>
            </div>
        </div>
    </section>

    @if($bill->payments->count() > 0)
        <section class="overflow-hidden rounded-xl border border-med-line bg-white shadow-panel bill-no-print">
            <header class="border-b border-med-line px-4 py-4 sm:px-6"><h2 class="font-semibold text-med-ink">Payment history</h2></header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-med-line text-sm">
                    <thead class="bg-med-canvas text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                        <tr><th class="px-4 py-3">Payment ID</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Recorded by</th><th class="px-4 py-3">Receipt</th></tr>
                    </thead>
                    <tbody class="divide-y divide-med-line">
                        @foreach($bill->payments as $payment)
                            <tr>
                                <td class="px-4 py-3 font-medium text-med-ink">{{ $payment->payment_id }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $payment->payment_method }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-med-muted">{{ $payment->recordedBy->name }}</td>
                                <td class="px-4 py-3"><a href="{{ route('accountant.payments.receipt', $payment) }}" class="mf-focus inline-flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-1.5 text-xs font-semibold text-med-primary hover:bg-med-canvas"><i class="bi bi-receipt" aria-hidden="true"></i> Print receipt</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<div id="bill-print-content" class="hidden">
    <article class="bill-print-card">
        <header class="flex items-start justify-between border-b border-gray-300 pb-4">
            <div><h1 class="text-xl font-bold">Bill statement</h1><p class="mt-1">Bill #: <strong>{{ $bill->bill_number }}</strong></p></div>
            <i class="bi bi-receipt-cutoff text-3xl" aria-hidden="true"></i>
        </header>
        <div class="grid grid-cols-2 gap-4 py-4">
            <div><p class="text-xs text-gray-500">Patient name</p><p class="font-semibold">{{ $patientName }}</p></div>
            <div><p class="text-xs text-gray-500">Hospital number</p><p class="font-semibold">{{ $hospitalNumber }}</p></div>
            <div><p class="text-xs text-gray-500">Bill status</p><p class="font-semibold">{{ ucfirst($bill->status) }}</p></div>
            <div><p class="text-xs text-gray-500">Issued date</p><p class="font-semibold">{{ $bill->issued_date->format('M d, Y') }}</p></div>
            <div><p class="text-xs text-gray-500">Due date</p><p class="font-semibold">{{ $bill->due_date->format('M d, Y') }}</p></div>
            <div><p class="text-xs text-gray-500">Discount</p><p class="font-semibold">{{ number_format($bill->discount, 0) }}%</p></div>
        </div>
        @if($bill->services->count() > 0 || $bill->investigations->count() > 0)
            <table class="bill-print-table">
                <thead><tr><th>Description</th><th>Unit price</th><th>Qty</th><th>Subtotal</th></tr></thead>
                <tbody>
                    @foreach($bill->services as $service)
                        <tr><td>{{ $service->name }}</td><td>{{ number_format($service->pivot->unit_price, 2) }}</td><td>{{ $service->pivot->quantity }}</td><td>{{ number_format($service->pivot->subtotal, 2) }}</td></tr>
                    @endforeach
                    @foreach($bill->investigations as $investigation)
                        <tr><td>{{ $investigation->name }}</td><td>{{ number_format($investigation->pivot->unit_price, 2) }}</td><td>{{ $investigation->pivot->quantity }}</td><td>{{ number_format($investigation->pivot->subtotal, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="3">Total</td><td>{{ number_format($bill->amount, 2) }}</td></tr></tfoot>
            </table>
        @endif
        <div class="grid grid-cols-3 gap-4 border-t border-gray-300 pt-4">
            <p>Amount due: <strong>{{ number_format($bill->due_amount, 2) }}</strong></p>
            <p>Total paid: <strong>{{ number_format($bill->totalPaid(), 2) }}</strong></p>
            <p>Balance: <strong>{{ number_format($bill->balance, 2) }}</strong></p>
        </div>
        <footer class="mt-6 border-t border-gray-300 pt-3 text-center text-sm">
            <p>This bill is generated by the system. Please keep it for your records.</p>
            <p class="mt-1">Issued by: {{ $bill->issuedBy->name }} · {{ $billDate }}</p>
        </footer>
    </article>
</div>

<style>
    .thermal-divider { border-top: 1px dashed currentColor; margin: .6rem 0; }
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body { background: #fff !important; color: #000 !important; }
        .bill-no-print, .mf-shell aside, .mf-shell > .min-h-screen > header, #bill-preview { display: none !important; }
        .mf-shell .min-h-screen { padding-left: 0 !important; }
        .mf-shell main { padding: 0 !important; }
        .mf-shell main > div { max-width: none !important; margin: 0 !important; }
        #bill-print-content { display: block !important; width: 100%; }
        .bill-print-card { border: 0; color: #000; font: 12pt Arial, sans-serif; }
        .bill-print-card p, .bill-print-card h1 { margin: 0; }
        .bill-print-card .grid { display: grid; }
        .bill-print-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .bill-print-table th, .bill-print-table td { border: 1px solid #bbb; padding: 7px; text-align: left; }
        .bill-print-table th:last-child, .bill-print-table td:last-child,
        .bill-print-table th:nth-child(2), .bill-print-table td:nth-child(2),
        .bill-print-table th:nth-child(3), .bill-print-table td:nth-child(3) { text-align: right; }
        .bill-print-table tfoot { font-weight: 700; }
    }
</style>

<script>
    function printThermalBill() {
        const content = document.querySelector('#thermal-bill-preview-card .thermal-bill-preview');
        if (!content) {
            alert('Thermal bill preview is unavailable.');
            return;
        }

        const printWindow = window.open('', '_blank', 'width=360,height=640');
        if (!printWindow) {
            alert('Please allow popups to print the thermal bill.');
            return;
        }

        printWindow.document.write(`<!doctype html><html><head><title>Thermal Bill</title><style>
            body{margin:8px;font:12px monospace;color:#000}
            h3,p{margin:0 0 4px}
            table{width:100%;border-collapse:collapse}
            td{padding:2px 0}
            td:last-child{text-align:right}
            dl{margin:0}
            .thermal-divider{border-top:1px dashed #000;margin:8px 0}
            .text-center{text-align:center}
            .flex{display:flex;justify-content:space-between;gap:8px}
            .text-right{text-align:right}
        </style></head><body>${content.innerHTML}</body></html>`);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function () {
            printWindow.print();
            printWindow.close();
        }, 300);
    }
</script>
@endsection
