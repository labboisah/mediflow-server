@extends('layouts.modern')
@section('content')<x-ui.page title="Specialist Clinical History" subtitle="{{ $patient->name() }} | {{ $patient->hospital_number }} | Read-only archive">
@forelse($consultations as $c)<x-ui.card title="Consultation #{{ $c->id }}" subtitle="{{ $c->created_at }} | {{ $c->status }} | {{ $c->specialist_name }}">
@foreach($c->clinical ?? [] as $key=>$value)<p class="whitespace-pre-wrap"><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</p>@endforeach
@foreach($c->amendments as $a)<p class="whitespace-pre-wrap">Amendment {{ $a->created_at }} by {{ $a->author_name }}: {{ $a->content }}</p>@endforeach
@foreach($c->carePlans as $plan)<p class="whitespace-pre-wrap">Care plan v{{ $plan->version }} ({{ $plan->status }}): {{ $plan->objectives }} / {{ $plan->instructions }}</p>@endforeach
</x-ui.card>@empty<p>No Specialist history.</p>@endforelse</x-ui.page>@endsection
