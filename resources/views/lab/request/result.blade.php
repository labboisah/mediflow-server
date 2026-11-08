@extends('layouts.modern')

@section('title', 'Laboratory Results')

@section('content')
    @php
        $completedRequests = $bill->investigationRequests->filter(function ($request) {
            return $request->investigationResults->filter(fn ($result) => filled($result->value))->isNotEmpty();
        })->values();
    @endphp

    <x-ui.page title="Combined Investigation Results" subtitle="{{ $bill->patientName() }}">
        <x-slot:actions><button type="button" onclick="window.print()" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark"><i class="bi bi-printer"></i>Print Results</button><a href="{{ route('lab.requests.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Requests</a></x-slot:actions>
        <div id="print" class="relative mx-auto w-full max-w-[190mm] bg-white">
            <div class="watermark-logo"></div>
            <div class="print-content rounded-md border border-med-line bg-white p-6 shadow-sm">
                <div class="text-center"><h2 class="text-xl font-bold text-med-primary">{{ strtoupper(config('app.title', config('app.name'))) }}</h2><h4 class="mt-1 text-sm font-semibold text-med-ink">DEPARTMENT OF {{ strtoupper(auth()->user()->department->name) }}</h4><p class="mt-1 text-sm text-med-danger"><em>{{ config('app.address') }}</em></p></div>
                <hr class="my-5 border-med-line">
                <div class="grid gap-4 md:grid-cols-2"><div><p class="text-sm text-med-muted">Patient Name</p><p class="font-semibold text-med-ink">{{ $bill->patientName() }}</p></div><div><p class="text-sm text-med-muted">Hospital Number</p><p class="font-semibold text-med-ink">{{ $bill->patientVisit ? $bill->patientVisit->patient->hospital_number : 'Walk-in Patient' }}</p></div></div>
                <div class="mt-6 space-y-6">
                    @forelse($completedRequests as $investigationRequest)
                        @php($uploadedResults = $investigationRequest->investigationResults->filter(fn ($result) => filled($result->value))->values())
                        <section class="result-section"><h5 class="font-bold text-med-ink">{{ $investigationRequest->investigation?->name ?? 'Investigation' }}</h5><p class="mt-1 text-sm text-med-muted">Lab No: {{ $investigationRequest->lab_no }}</p><div class="mt-3 overflow-x-auto"><table class="min-w-full border-collapse text-sm"><thead><tr><th class="border border-med-line px-3 py-2 text-left">Parameter</th><th class="border border-med-line px-3 py-2 text-left">Value</th><th class="border border-med-line px-3 py-2 text-left">Reference Range</th></tr></thead><tbody>@foreach($uploadedResults as $result)<tr><td class="border border-med-line px-3 py-2">{{ $result->parameter?->name ?? 'Parameter' }}</td><td class="border border-med-line px-3 py-2">{{ $result->value }}</td><td class="border border-med-line px-3 py-2">{{ $result->parameter?->reference_range ?? 'N/A' }}</td></tr>@endforeach</tbody></table></div></section>
                    @empty
                        <x-ui.empty-state title="No Uploaded Results" message="No uploaded lab results are available for this bill." />
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.page>

    <style>
        .watermark-logo { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); opacity:.06; z-index:0; width:300px; height:300px; background:url('{{ asset("images/logo.png") }}') center/contain no-repeat; pointer-events:none; }
        .print-content { position:relative; z-index:2; }
        @page { size:A4; margin:8mm; }
        @media print { .admin-sidebar,.hospital-navbar,.modern-topbar,.no-print,button,a[href] { display:none !important; } body, .print-content { font-size:12px; } #print { width:194mm; box-shadow:none !important; } .print-content { border:0 !important; box-shadow:none !important; padding:0 !important; } table { width:100%; border-collapse:collapse; } th,td { padding:4px 6px !important; font-size:11px; } .result-section, table, thead, tbody { page-break-inside:avoid !important; break-inside:avoid !important; } }
    </style>
@endsection
