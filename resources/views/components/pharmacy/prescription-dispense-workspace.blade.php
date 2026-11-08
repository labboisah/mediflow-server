<div>
    @php($patient = $prescription->patientVisit?->patient)

    <x-ui.page title="Prescription Dispensing" subtitle="{{ $patient?->demographic?->full_name ?? 'Patient' }} | {{ $patient?->hospital_number ?? 'N/A' }}">
        <x-slot:actions>
            <a href="{{ route('pharmacy.prescriptions.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Prescriptions</a>
            @if($unavailableRows->isNotEmpty())
                <x-ui.button type="button" variant="secondary" onclick="printUnavailablePrescription()"><i class="bi bi-printer"></i>Print Unavailable</x-ui.button>
            @endif
        </x-slot:actions>

        @if($isPaid)
            <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm font-semibold text-med-primary">
                <i class="bi bi-check-circle"></i> This prescription has already been paid. Additional payment is blocked.
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <x-ui.card title="Prescribed Medicines" subtitle="Select available medicines to dispense.">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-md bg-med-canvas px-4 py-3">
                        <span class="text-sm font-medium text-med-muted">Selected Bill Amount</span>
                        <span class="text-xl font-bold text-med-ink">&#8358;{{ number_format($selectedTotal, 2) }}</span>
                    </div>
                    <x-ui.table class="shadow-none">
                        <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3">Dispense</th><th class="px-4 py-3">Medicine</th><th class="px-4 py-3">Prescription</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @foreach($rows as $row)
                                <tr wire:key="prescription-dispense-{{ $row['item_id'] }}">
                                    <td class="px-4 py-4"><input type="checkbox" class="mf-focus h-4 w-4 rounded border-med-line text-med-primary" wire:model.live="selected.{{ $row['item_id'] }}" @disabled($isPaid || $row['available'] <= 0 || $row['status'] !== 'Started')></td>
                                    <td class="px-4 py-4"><p class="font-semibold text-med-ink">{{ $row['medicine'] }}</p>@if($row['generic'])<p class="text-sm text-med-muted">{{ $row['generic'] }}</p>@endif<p class="text-sm text-med-muted">{{ $row['company'] }}</p></td>
                                    <td class="px-4 py-4 text-sm text-med-muted"><p><span class="font-semibold text-med-ink">Route:</span> {{ $row['route'] }}</p><p><span class="font-semibold text-med-ink">Dosage:</span> {{ $row['dosage'] }}</p><p><span class="font-semibold text-med-ink">Period:</span> {{ $row['period'] }}</p><p><span class="font-semibold text-med-ink">Duration:</span> {{ $row['duration'] }}</p><p><span class="font-semibold text-med-ink">Suggested:</span> {{ $row['suggested_quantity'] }}</p></td>
                                    <td class="px-4 py-4">@if($row['available'] > 0)<x-ui.badge variant="success">{{ $row['available'] }} available</x-ui.badge>@else<x-ui.badge variant="danger">Not available</x-ui.badge>@endif @if($row['shortage'] > 0)<p class="mt-2 text-sm text-med-danger">Short by {{ $row['shortage'] }}</p>@endif @if($row['status'] !== 'Started')<p class="mt-2 text-sm text-orange-700">Medication stopped</p>@endif</td>
                                    <td class="px-4 py-4 text-right"><input type="number" min="0" max="{{ $row['available'] }}" class="mf-focus ml-auto block w-24 rounded-md border border-med-line px-3 py-2 text-right text-sm text-med-ink shadow-sm" wire:model.live="quantities.{{ $row['item_id'] }}" @disabled($isPaid || $row['available'] <= 0 || $row['status'] !== 'Started')><p class="mt-1 text-xs text-med-muted">&#8358;{{ number_format($row['unit_price'], 2) }} each</p></td>
                                    <td class="px-4 py-4 text-right font-semibold text-med-ink">&#8358;{{ number_format($row['selected'] ? $row['amount'] : 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-med-canvas"><tr><th colspan="5" class="px-4 py-3 text-right text-med-ink">Amount to Collect</th><th class="px-4 py-3 text-right text-med-ink">&#8358;{{ number_format($selectedTotal, 2) }}</th></tr></tfoot>
                    </x-ui.table>
                </x-ui.card>
            </div>

            <div class="space-y-6 xl:col-span-4">
                <x-ui.card title="Payment">
                    <div class="space-y-4">
                        <x-ui.select label="Payment Method" wire:model="paymentMethodId" name="paymentMethodId" :disabled="$isPaid"><option value="">Select method</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</x-ui.select>
                        <x-ui.input label="Reference" wire:model="referenceNumber" placeholder="Optional POS/transfer reference" :disabled="$isPaid" />
                        @error('selected')<div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-med-danger">{{ $message }}</div>@enderror
                        <x-ui.button type="button" class="w-full" wire:click="dispense" wire:loading.attr="disabled" :disabled="$isPaid"><span wire:loading.remove wire:target="dispense">{{ $isPaid ? 'Already Paid' : 'Record Payment & Dispense' }}</span><span wire:loading wire:target="dispense">Saving...</span></x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card title="Unavailable / Shortage">
                    <div class="divide-y divide-med-line">
                        @forelse($unavailableRows as $row)
                            <div class="py-3 first:pt-0 last:pb-0"><p class="font-semibold text-med-ink">{{ $row['medicine'] }}</p><p class="text-sm text-med-muted">{{ $row['dosage'] }} | {{ $row['period'] }} | {{ $row['duration'] }}</p><p class="text-sm text-med-danger">Required {{ $row['suggested_quantity'] }}, available {{ $row['available'] }}</p></div>
                        @empty
                            <x-ui.empty-state title="All Medicines Available" message="No unavailable medicine is listed for this prescription." />
                        @endforelse
                    </div>
                </x-ui.card>

                @if($receiptTransaction)
                    <x-ui.card title="Receipt Ready" subtitle="{{ $receiptTransaction->payment?->payment_id }} | {{ $receiptTransaction->bill?->bill_number }}">
                        <div class="flex items-end justify-between gap-3">
                            <div><p class="text-sm text-med-muted">Amount Paid</p><p class="mt-1 text-2xl font-bold text-med-ink">&#8358;{{ number_format($receiptTransaction->payment?->amount ?? 0, 2) }}</p></div>
                            <x-ui.button type="button" variant="secondary" onclick="printDispenseReceipt()"><i class="bi bi-printer"></i>Thermal</x-ui.button>
                        </div>
                    </x-ui.card>
                @endif
            </div>
        </div>
    </x-ui.page>

    <div id="unavailable-prescription-print" class="hidden">
        <div class="print-prescription">
            <div class="text-center"><h4>{{ strtoupper(config('app.title') ?? config('app.name')) }}</h4><div>{{ config('app.address') }}</div><h5 class="mt-2">Unavailable Prescription Items</h5></div>
            <hr>
            <p><strong>Patient:</strong> {{ $patient?->demographic?->full_name ?? 'N/A' }}</p>
            <p><strong>Hospital No:</strong> {{ $patient?->hospital_number ?? 'N/A' }}</p>
            <p><strong>Doctor:</strong> {{ $prescription->prescribedBy?->name ?? 'N/A' }} | {{ $prescription->prescribedBy?->department?->name ?? 'N/A' }}</p>
            <p><strong>Treatment:</strong> {{ $prescription->treatment_diagnosis ?? 'N/A' }}</p>
            <table><thead><tr><th>Medicine</th><th>Route</th><th>Dosage</th><th>Period</th><th>Duration</th><th>Needed</th></tr></thead><tbody>@foreach($unavailableRows as $row)<tr><td>{{ $row['medicine'] }}</td><td>{{ $row['route'] }}</td><td>{{ $row['dosage'] }}</td><td>{{ $row['period'] }}</td><td>{{ $row['duration'] }}</td><td>{{ max(1, $row['shortage'] ?: $row['suggested_quantity']) }}</td></tr>@endforeach</tbody></table>
            <p class="mt-3"><strong>Pharmacy Note:</strong> The medicines listed above are not available or not fully available at this pharmacy.</p>
            <p><strong>Date:</strong> {{ now()->format('d M, Y h:i A') }}</p>
        </div>
    </div>

    @if($receiptTransaction)
        <div id="dispense-receipt-print" class="hidden">
            <div class="thermal-receipt">
                <div class="text-center"><h5>{{ strtoupper(config('app.title') ?? config('app.name')) }}</h5><div>{{ strtoupper(config('app.address') ?? '') }}</div><strong>PHARMACY RECEIPT</strong></div>
                <div class="divider"></div>
                <p><strong>Receipt:</strong> {{ $receiptTransaction->payment?->payment_id }}</p><p><strong>Bill:</strong> {{ $receiptTransaction->bill?->bill_number }}</p><p><strong>Patient:</strong> {{ $patient?->demographic?->full_name ?? 'N/A' }}</p><p><strong>Hospital No:</strong> {{ $patient?->hospital_number ?? 'N/A' }}</p><p><strong>Method:</strong> {{ $receiptTransaction->payment?->paymentMethod?->name ?? 'N/A' }}</p>
                <div class="divider"></div>
                <table>@foreach($receiptTransaction->stockTransactionItems as $item)<tr><td>{{ \Illuminate\Support\Str::limit($item->medicineBatch?->medicine?->name ?? 'N/A', 20) }}</td><td class="text-right">{{ $item->quantity }} x {{ number_format($item->price, 2) }}</td></tr><tr><td class="small">{{ $item->prescriptionItem?->dosage }} | {{ $item->prescriptionItem?->duration }}</td><td class="text-right">{{ number_format($item->subtotal, 2) }}</td></tr>@endforeach</table>
                <div class="divider"></div><p><strong>Total Paid:</strong> {{ number_format($receiptTransaction->payment?->amount ?? 0, 2) }}</p><p><strong>Served By:</strong> {{ $receiptTransaction->createdBy?->name ?? 'System' }}</p><div class="divider"></div><p class="text-center">Thank you.</p>
            </div>
        </div>
    @endif

    <style>
        .thermal-receipt, .print-prescription { font-family: monospace; color: #000; }
        .thermal-receipt { width: 72mm; max-width: 72mm; font-size: 13.5px; line-height: 1.25; }
        .thermal-receipt p { margin: 0 0 3px; }
        .thermal-receipt table, .print-prescription table { width: 100%; border-collapse: collapse; }
        .thermal-receipt td, .print-prescription th, .print-prescription td { padding: 3px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .thermal-receipt .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .text-right { text-align: right; }
    </style>

    @push('scripts')
        @include('pharmacy.partials.thermal-printer')
        <script>
            function openPrintWindow(html, title, width = 420, height = 700, thermal = false) {
                if (thermal) { printPharmacyReceipt(html); return; }
                const printWindow = window.open('', '_blank', `width=${width},height=${height}`);
                if (!printWindow) { alert('Please allow popups to print.'); return; }
                printWindow.document.write('<html><head><title>' + title + '</title>');
                printWindow.document.write('<style>@page{size:A4 portrait;margin:12mm;} body{margin:0;font-family:monospace;color:#000;font-size:13px;} table{width:100%;border-collapse:collapse;} th,td{padding:4px;border-bottom:1px solid #ddd;vertical-align:top;} .text-center{text-align:center;} .text-right{text-align:right;} .small{font-size:12px;}</style>');
                printWindow.document.write('</head><body>' + html + '</body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(function () { printWindow.print(); printWindow.close(); }, 300);
            }
            function printUnavailablePrescription() { const template = document.getElementById('unavailable-prescription-print'); if (template) { openPrintWindow(template.innerHTML, 'Unavailable Prescription', 780, 900); } }
            function printDispenseReceipt() { const template = document.getElementById('dispense-receipt-print'); if (template) { openPrintWindow(template.innerHTML, 'Pharmacy Receipt', 420, 700, true); } }
            document.addEventListener('livewire:init', function () { Livewire.on('print-prescription-dispense-receipt', function () { setTimeout(printDispenseReceipt, 400); }); });
        </script>
    @endpush
</div>

