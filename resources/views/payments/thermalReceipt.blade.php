<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt</title>

<style>
    body {
        font-family: monospace;
        font-size: 12px;
        width: 80mm;
        margin: 0;
        padding: 5px;
    }

    .center { text-align: center; }
    .bold { font-weight: bold; }
    .bill-no { border: 1px dashed #000; font-size: 16px; margin: 5px 0; padding: 4px; text-align: center; }
    .line { border-top: 1px dashed #000; margin: 5px 0; }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    td {
        padding: 2px 0;
    }

    .text-right { text-align: right; }
</style>
</head>

<body onload="window.print()">
<div class="no-print">
    <button class="btn btn-back" onclick="window.history.back()">← Back</button>
    <button class="btn btn-print" onclick="window.print()">Print</button>
</div>
<div class="center bold">
    ANNEX SYSTEM<br>
    Payment Receipt
</div>

<div class="line"></div>

<div class="bill-no">Bill No: {{ $bill->bill_no }}</div>
<p>Receipt: {{ $bill->id }}</p>
<p>Date: {{ $bill->created_at->format('d/m/Y H:i') }}</p>

<div class="line"></div>

<p>Patient: {{ $bill->patient_name ?? 'N/A' }}</p>

<div class="line"></div>

<table>
    <tr>
        <td>Total Bill:</td>
        <td class="text-right">₦{{ number_format($bill->amount,2) }}</td>
    </tr>
    <tr>
        <td>Total Paid:</td>
        <td class="text-right">₦{{ number_format($bill->total_paid,2) }}</td>
    </tr>
    <tr>
        <td>Balance:</td>
        <td class="text-right">₦{{ number_format($bill->balance,2) }}</td>
    </tr>
</table>
<div class="line"></div>
<!-- Services paying for -->
<p class="bold">SERVICES</p>

    <table>
        <tr>
            <th>Service</th>
            <th class="text-right">Amount</th>
        </tr>
        @foreach($bill->items as $billItem)
        <tr>
            <td>{{ $billItem->service->name }}</td>
            <td class="text-right">₦{{ number_format($billItem->service->price,2) }}</td>
        </tr>
        @endforeach
    </table>

<div class="line"></div>


<p class="bold">PAYMENT HISTORY</p>


    <table>
        <tr>
            <th>Date</th>
            <th>Method</th>
            <th class="text-right">Amount</th>
        </tr>
        @foreach($bill->payments as $p)
        <tr>
            <td>{{ $p->created_at->format('d M, Y') }}</td>
            <td>{{ ucfirst($p->payment_method) }}</td>
            <td class="text-right">₦{{ number_format($p->amount,2) }}</td>
        </tr>
        @endforeach
    </table>

<div class="line"></div>


<p class="center">
    @if($bill->balance <= 0)
        PAID
    @else
        PARTIAL
    @endif
</p>

<div class="line"></div>

<p class="center">Thank you</p>

</body>
</html>
