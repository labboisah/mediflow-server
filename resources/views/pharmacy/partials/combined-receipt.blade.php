<div class="thermal-receipt">
    <div class="text-center"><h5>{{ strtoupper(config('app.title') ?? config('app.name')) }}</h5><div>{{ strtoupper(config('app.address') ?? '') }}</div><strong>PHARMACY RECEIPT</strong></div>
    <div class="divider"></div>
    <p><strong>Receipt:</strong> {{ $payment->payment_id }}</p>
    <p><strong>Bill:</strong> {{ $transaction->bill?->bill_number }}</p>
    <p><strong>Date:</strong> {{ $payment->payment_date?->format('M d, Y h:i A') }}</p>
    @if($transaction->patient_name)<p><strong>Patient:</strong> {{ $transaction->patient_name }}</p>@endif
    <p><strong>Method:</strong> {{ $payment->paymentMethod?->name ?? 'N/A' }}</p>
    @if($payment->reference_number)<p><strong>Ref:</strong> {{ $payment->reference_number }}</p>@endif
    <div class="divider"></div>
    <table>
        @foreach($transaction->stockTransactionItems as $item)
            <tr><td>{{ $item->medicineBatch?->medicine?->name ?? 'N/A' }}</td><td class="text-right">{{ $item->quantity }} x {{ number_format($item->price, 2) }}</td></tr>
            <tr><td class="small">Batch {{ $item->medicineBatch?->batch_number ?? 'N/A' }}</td><td class="text-right">{{ number_format($item->subtotal, 2) }}</td></tr>
        @endforeach
        @foreach($transaction->serviceItems as $item)
            <tr><td>{{ $item->name }}</td><td class="text-right">{{ $item->quantity }} x {{ number_format($item->price, 2) }}</td></tr>
            <tr><td class="small">Service</td><td class="text-right">{{ number_format($item->subtotal, 2) }}</td></tr>
        @endforeach
    </table>
    <div class="divider"></div>
    <p><strong>Total Paid:</strong> {{ number_format($payment->amount, 2) }}</p>
    <p><strong>Served By:</strong> {{ $transaction->createdBy?->name ?? 'System' }}</p>
    <div class="divider"></div><p class="text-center">Thank you.</p>
</div>
