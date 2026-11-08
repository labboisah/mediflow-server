<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - {{ $result->bill->bill_no }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f3f4f6;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 16mm 18mm;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }
        .watermark {
            left: 50%;
            opacity: 0.06;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            z-index: 0;
        }
        .report-content {
            position: relative;
            z-index: 1;
        }
        .header {
            border-bottom: 3px solid #0F2D5C;
            padding-bottom: 12px;
            margin-bottom: 18px;
            text-align: center;
        }
        .brand {
            color: #0F2D5C;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .sub-brand {
            color: #16A34A;
            font-size: 18px;
            font-weight: 700;
            margin-top: 2px;
        }
        .address {
            font-size: 12px;
            margin-top: 4px;
        }
        .report-title {
            background: #0F2D5C;
            color: white;
            font-size: 15px;
            font-weight: 700;
            margin: 12px auto 0;
            padding: 6px 18px;
            text-transform: uppercase;
            width: fit-content;
        }
        .top-meta {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
            font-size: 13px;
            margin-bottom: 24px;
        }
        .section {
            margin-top: 22px;
        }
        .section h3 {
            color: #0F2D5C;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
            font-size: 15px;
            text-transform: uppercase;
        }
        .content {
            white-space: pre-line;
            line-height: 1.7;
            font-size: 14px;
        }
        .signature {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
            font-size: 13px;
        }
        .line {
            border-top: 1px solid #111827;
            padding-top: 8px;
            width: 220px;
            text-align: center;
        }
        .actions {
            width: 210mm;
            margin: 20px auto 0;
            text-align: right;
        }
        .actions button,
        .actions a {
            background: #16A34A;
            border: 0;
            border-radius: 8px;
            color: white;
            display: inline-block;
            font-size: 14px;
            padding: 10px 16px;
            text-decoration: none;
        }
        @media print {
            body {
                background: white;
            }
            .actions {
                display: none;
            }
            .sheet {
                margin: 0;
                width: auto;
                min-height: auto;
                padding: 16mm;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('staff.results.reports') }}">Back to Reports</a>
        <button onclick="window.print()">Print Result</button>
    </div>

    <main class="sheet">
        <img src="{{ asset('images/logo.png') }}" class="watermark" alt="">

        <div class="report-content">
            <div class="header">
                <div class="brand">MEDISTOP CLINICAL DIAGNOSTICS</div>
                <div class="sub-brand">ANNEX</div>
                <div class="address">SULTAN ABUBAKAR ROAD, MARINA</div>
                <div class="address">Contact: 07036354477, 08035677283</div>
                <div class="report-title">Diagnostic Report</div>
            </div>

            <div class="top-meta">
                <div><strong>Bill No:</strong> {{ $result->bill->bill_no }}</div>
                <div><strong>Result Date:</strong> {{ optional($result->completed_at)->format('d M Y h:i A') }}</div>
            </div>

            <div class="meta">
                <div><strong>Patient:</strong> {{ $result->bill->patient_name ?? 'Walk-in' }}</div>
                <div><strong>Investigation:</strong> {{ $result->billItem->service->name }}</div>
                <div><strong>Category:</strong> {{ optional($result->billItem->service->category)->name ?? 'N/A' }}</div>
                <div><strong>Bill Date:</strong> {{ $result->bill->created_at->format('d M Y h:i A') }}</div>
                <div><strong>Bill Recorded By:</strong> {{ optional($result->bill->user)->name ?? 'Unknown' }}</div>
                <div><strong>Performed By:</strong> {{ optional($result->performer)->name ?? 'N/A' }}</div>
                <div><strong>Reported By:</strong> {{ optional($result->reporter)->name ?? optional($result->staff)->name ?? 'N/A' }}</div>
                <div><strong>Payment Status:</strong> {{ strtoupper($result->bill->payment_status) }}</div>
                <div><strong>Total Paid:</strong> NGN {{ number_format($result->bill->total_paid, 2) }}</div>
            </div>

            @if($result->clinical_note)
                <section class="section">
                    <h3>Clinical Note</h3>
                    <div class="content">{{ $result->clinical_note }}</div>
                </section>
            @endif

            <section class="section">
                <h3>Findings</h3>
                <div class="content">{{ $result->findings }}</div>
            </section>

            @if($result->impression)
                <section class="section">
                    <h3>Impression</h3>
                    <div class="content">{{ $result->impression }}</div>
                </section>
            @endif

            <div class="signature">
                <div class="line">Staff Signature</div>
                <div class="line">Authorized Signature</div>
            </div>
        </div>
    </main>
</body>
</html>
