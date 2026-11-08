<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $payment->id }}</title>

    <style>
        body { font-family: Arial, sans-serif; color: #222; position: relative; }
        .receipt-container { max-width: 820px; margin: auto; border: 1px solid #ccc; padding: 16px; position: relative; }
        .header { text-align: center; margin-bottom: 16px; }
        .section { margin-bottom: 14px; page-break-inside: avoid; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .bold { font-weight: 700; }
        .no-print { margin-bottom: 12px; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); opacity: 0.08; z-index: -1; }
        .signature-section { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 20px; }
        .signature-line { display: inline-block; width: 250px; border-bottom: 1px solid #000; }
        .bill-no-box { border: 2px solid #111; display: inline-block; font-size: 22px; margin: 8px 0; padding: 8px 18px; }

        .badge-paid { color: green; font-weight: bold; }
        .badge-partial { color: orange; font-weight: bold; }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .receipt-container { border: none; width: 100%; }
        }
    </style>
</head>

<body>

<div class="receipt-container">

    <!-- Buttons -->
    <div class="no-print">
        <button onclick="window.history.back()" style="padding:8px 16px;">← Back</button>
        <button onclick="window.print()" style="padding:8px 16px;">Print A4</button>
        <button style="padding:8px 16px;" onclick="window.location.href='{{ route('payments.receipt', ['bill' => $bill, 'type' => 'thermal']) }}'">
             Print Thermal
        </button>
    </div>

    <!-- Watermark -->
    <div class="watermark">
        <img src="{{ asset('images/logo.png') }}" style="width:300px;">
    </div>

    <!-- Header -->
    <div class="header section">
        <img src="{{ asset('images/logo.png') }}" style="width:100px;">
        <h2>Payment Receipt</h2>
        <div class="bill-no-box"><strong>Bill No:</strong> {{ $bill->bill_no }}</div>
        <p><strong>Receipt ID:</strong> {{ $payment->id }}</p>
        <p><strong>Date:</strong> {{ now()->format('d M Y H:i') }}</p>
    </div>

    <!-- Bill Info -->
    <div class="section">
        <h4>Bill Information</h4>
        <p><strong>Bill No:</strong> {{ $bill->bill_no }}</p>
        <p><strong>Patient:</strong> {{ $bill->patient_name ?? 'N/A' }}</p>
        <p><strong>Bill Created By:</strong> {{ optional($bill->user)->name ?? 'Unknown' }}</p>
        <p><strong>Payment Recorded By:</strong> {{ optional($payment->user)->name ?? 'N/A' }}</p>
    </div>

    <!-- Payment Summary -->
    <div class="section">
        <h4>Payment Summary</h4>

        <p><strong>Total Bill:</strong> ₦{{ number_format($bill->amount, 2) }}</p>
        <p><strong>Total Paid:</strong> ₦{{ number_format($bill->total_paid, 2) }}</p>
        <p><strong>Outstanding Balance:</strong> ₦{{ number_format($bill->balance, 2) }}</p>

        <p>
            <strong>Status:</strong>
            @if($bill->balance <= 0)
                <span class="badge-paid">PAID</span>
            @else
                <span class="badge-partial">PARTIALLY PAID</span>
            @endif
        </p>

        <hr>

        <p><strong>This Payment:</strong> ₦{{ number_format($payment->amount, 2) }}</p>
        <p><strong>Method:</strong> {{ $payment->payment_method ?? 'Cash' }}</p>
        <p><strong>Date:</strong> {{ $payment->created_at->format('d M Y H:i') }}</p>

        @if($payment->note)
            <p><strong>Note:</strong> {{ $payment->note }}</p>
        @endif
    </div>

    <!-- Payment History -->
    @if($bill->payments->count() > 1)
    <div class="section">
        <h4>Payment History</h4>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->payments as $index => $p)
                    <tr style="{{ $p->id === $payment->id ? 'background:#e0f2fe;' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $p->created_at->format('d M Y H:i') }}</td>
                        <td>₦{{ number_format($p->amount, 2) }}</td>
                        <td>{{ $p->payment_method ?? 'Cash' }}</td>
                        <td>{{ optional($p->user)->name ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Services -->
    @php
        $items = $bill->items ?? collect();
        $showDistribution = $items->where('revenueDistribution', '!=', null)->filter(function($item){
            return $item->revenueDistribution &&
                ($item->revenueDistribution->radiologist_amount > 0 ||
                 $item->revenueDistribution->radiographer_amount > 0 ||
                 $item->revenueDistribution->staff_amount > 0 ||
                 $item->revenueDistribution->annex_amount > 0);
        })->isNotEmpty();
    @endphp

    @if($items->isNotEmpty())
    <div class="section">
        <h4>Services</h4>

        <table class="table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Price</th>
                    @if($showDistribution)
                        <th class="text-right">Staff</th>
                        <th class="text-right">Annex</th>
                        <th class="text-right">Radiologist</th>
                        <th class="text-right">Radiographer</th>
                    @endif
                </tr>
            </thead>
            <tbody>

            @foreach($items as $item)
                @php $d = $item->revenueDistribution; @endphp
                <tr>
                    <td>{{ optional($item->service)->name ?? 'Unknown' }}</td>
                    <td class="text-right">₦{{ number_format($item->price, 2) }}</td>

                    @if($showDistribution)
                        <td class="text-right">{{ $d && $d->staff_amount > 0 ? '₦'.number_format($d->staff_amount,2) : '-' }}</td>
                        <td class="text-right">{{ $d && $d->annex_amount > 0 ? '₦'.number_format($d->annex_amount,2) : '-' }}</td>
                        <td class="text-right">{{ $d && $d->radiologist_amount > 0 ? '₦'.number_format($d->radiologist_amount,2) : '-' }}</td>
                        <td class="text-right">{{ $d && $d->radiographer_amount > 0 ? '₦'.number_format($d->radiographer_amount,2) : '-' }}</td>
                    @endif
                </tr>
            @endforeach

            </tbody>
        </table>
    </div>
    @endif

    <!-- Signature -->
    <div class="signature-section">
        <div style="text-align:right;">
            <p>Authorized Signature:</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="section" style="text-align:center; margin-top:20px;">
        <p>*** This receipt is system generated and valid ***</p>
    </div>

</div>

</body>
</html>
