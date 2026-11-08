@extends('layouts.modern')
@section('content')<x-ui.page title="Shared Patient History" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }}">
<x-slot:actions><a href="{{ route('specialist.index') }}">Workspace</a></x-slot:actions>
@foreach($consultations as $c)<x-ui.card title="Specialist consultation #{{ $c->id }}" subtitle="{{ $c->specialist_name }} | {{ $c->status }} | {{ $c->created_at }}">@foreach($c->clinical ?? [] as $key=>$value)<p class="whitespace-pre-wrap"><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>@endforeach
@foreach($c->amendments as $a)<p class="whitespace-pre-wrap">Amendment {{ $a->created_at }} by {{ $a->author_name }}: {{ $a->content }}</p>@endforeach</x-ui.card>@endforeach
@foreach($visits as $visit)<x-ui.card title="{{ $visit->visit_type }} visit" subtitle="{{ $visit->visit_date }} | {{ $visit->status }}"><p>{{ $visit->reason_for_visit }}</p><p class="whitespace-pre-wrap">{{ $visit->clinical_notes }}</p>
@foreach($visit->continuations as $note)<p class="whitespace-pre-wrap">{{ $note->created_at }} / {{ $note->writtenBy?->name }}: {{ $note->note }}
History: {{ $note->history }}
Examination: {{ $note->examination }}
Findings: {{ $note->diagnose }}
Plan: {{ $note->plan }}</p>@endforeach
@foreach($visit->prescriptions as $p)@foreach($p->prescriptionItems as $item)<p>Medication: {{ $item->medicine_name ?? $item->medicine?->name }} / {{ $item->dosage }} / {{ $item->duration }}</p>@endforeach @endforeach
@foreach($visit->investigationRequests as $order)<p>Investigation: {{ $order->requested_name ?? $order->investigation?->name }} / {{ $order->status }}</p>@endforeach
@foreach($visit->admissions as $admission)<p>Admission #{{ $admission->id }} / {{ $admission->status }} @if($admission->discharge) / Discharged {{ $admission->discharge->created_at }} @endif</p>@endforeach
</x-ui.card>@endforeach{{ $visits->links() }}</x-ui.page>@endsection
