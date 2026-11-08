@extends('layouts.modern')

@section('title', 'Radiology Result')

@section('content')
    <style>
        #print {
            position: relative;
            overflow: hidden;
            background: white;
        }

        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            z-index: 0;
            width: 300px;
            height: 300px;
            background-image: url('{{ asset("images/logo.png") }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            pointer-events: none;
        }

        .print-content {
            position: relative;
            z-index: 2;
        }

        @page { size: A4; margin: 8mm; }

        @media print {
            html, body { width: 210mm; height: 297mm; margin: 0; }
            body, .print-content { font-size: 12px; }
            h2 { font-size: 16px; margin: 0 0 4px 0; }
            h4 { font-size: 13px; margin: 0 0 6px 0; }
            table { border-collapse: collapse; width: 100%; }
            th, td { padding: 4px 6px !important; font-size: 11px; }
            .p-3, .p-4, .p-6 { padding: 6px !important; }
            img { max-width: 180mm !important; height: auto !important; }
            .print-section, .result-section, table, thead, tbody, tfoot { page-break-inside: avoid !important; break-inside: avoid !important; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            h1,h2,h3,h4,h5,h6 { page-break-after: avoid !important; break-after: avoid !important; }
            p, li { orphans: 2; widows: 2; }
            body * { visibility: hidden; }
            #print, #print * { visibility: visible; }
            #print { position: absolute; left: 0; top: 0; width: auto; background: white; }
            .watermark-logo { opacity: 0.06 !important; }
            .no-print { display: none !important; }
        }
    </style>

    <x-ui.page title="Radiology Investigation Result" subtitle="{{ $patientName }}{{ $hospitalNumber ? ' | ' . $hospitalNumber : ' | Walk-in Patient' }}">
        <x-slot name="actions">
            <button onclick="window.print()" type="button" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
                Print Result
            </button>
            <a href="{{ route('radiology.requests.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">
                Back to Requests
            </a>
        </x-slot>

        <section id="print" class="rounded-md border border-med-line bg-white shadow-sm">
            <div class="watermark-logo"></div>

            <div class="print-content p-6">
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-bold text-med-success">{{ config('app.name', 'MediFlow') }}</h2>
                    <h4 class="mt-2 text-sm font-semibold uppercase tracking-wide text-med-muted">Department of {{ strtoupper(auth()->user()->department->name) }}</h4>
                </div>

                <div class="border-y border-med-line py-4">
                    <div class="grid gap-3 text-sm md:grid-cols-2">
                        <p class="text-med-muted">Patient Name: <strong class="text-med-ink">{{ $patientName }}</strong></p>
                        <p class="text-med-muted">Hospital Number: <strong class="text-med-ink">{{ $hospitalNumber ?: 'Walk-in Patient' }}</strong></p>
                        <p class="text-med-muted">Requested At: <strong class="text-med-ink">{{ $investigationRequest->created_at->format('d M, Y @ h:i A') }}</strong></p>
                        <p class="text-med-muted">Requested By: <strong class="text-med-ink">{{ $investigationRequest->requestedBy->name ?? 'N/A' }}</strong></p>
                        <p class="text-med-muted">Performed By: <strong class="text-med-ink">{{ $investigationRequest->performedBy->name ?? 'N/A' }}</strong></p>
                    </div>
                </div>

                                @if($investigationRequest->diagnosticReport)
                    <div class="mt-5 grid gap-4 border-b border-med-line pb-5 text-sm">
                        <div><h5 class="font-semibold text-med-ink">Clinical Note</h5><p class="mt-1 whitespace-pre-line text-med-muted">{{ $investigationRequest->diagnosticReport->clinical_note ?: '—' }}</p></div>
                        <div><h5 class="font-semibold text-med-ink">Findings</h5><p class="mt-1 whitespace-pre-line text-med-muted">{{ $investigationRequest->diagnosticReport->findings ?: '—' }}</p></div>
                        <div><h5 class="font-semibold text-med-ink">Impression</h5><p class="mt-1 whitespace-pre-line text-med-muted">{{ $investigationRequest->diagnosticReport->impression ?: '—' }}</p></div>
                    </div>
                @endif
<div class="result-section mt-6">
                    <h5 class="mb-3 text-lg font-semibold text-med-ink">{{ $investigationRequest->investigation->name }}</h5>

                    @if($investigationRequest->investigationResults->isEmpty())
                        <div class="rounded-md border border-med-warning/30 bg-med-warning/10 px-4 py-3 text-sm font-medium text-med-warning">No results recorded yet.</div>
                    @else
                        <div class="overflow-hidden rounded-md border border-med-line">
                            <table class="min-w-full divide-y divide-med-line text-sm">
                                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted">
                                    <tr>
                                        <th class="px-4 py-3">Parameter</th>
                                        <th class="px-4 py-3">Value</th>
                                        <th class="px-4 py-3">Reference Range</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-med-line bg-white">
                                    @foreach($investigationRequest->investigationResults as $result)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold text-med-ink">{{ $result->parameter->name ?? 'Parameter' }}</td>
                                            <td class="px-4 py-3 text-med-muted">{{ $result->value }}</td>
                                            <td class="px-4 py-3 text-med-muted">{{ $result->parameter->reference_range }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($investigationRequest->result_image)
                        <div class="mt-6">
                            <h6 class="mb-2 text-sm font-semibold text-med-ink">Attached Image</h6>
                            <a href="{{ route('radiology.requests.image', $investigationRequest) }}" target="_blank">
                                <img src="{{ route('radiology.requests.image', $investigationRequest) }}" class="max-h-[720px] w-full rounded-md border border-med-line object-contain" alt="Radiology Image">
                            </a>
                            <p class="no-print mt-2 text-sm text-med-muted">Click image to open full size in a new tab.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </x-ui.page>
@endsection


