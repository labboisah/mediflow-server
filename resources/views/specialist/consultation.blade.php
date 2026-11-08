@extends('layouts.modern')
@section('content')
<x-ui.page title="Consultation #{{ $c->id }}" subtitle="{{ $c->patient->name() }} | {{ $c->patient->hospital_number }} | {{ $c->specialist_name }}">
<x-slot:actions><a class="text-med-primary underline" href="{{ route('specialist.index') }}">Workspace</a><a class="text-med-primary underline" href="{{ route('specialist.index',['followup_id'=>$c->id]) }}">Book follow-up</a></x-slot:actions>
@if(auth()->user()->hasPermission('partner_collaboration.create') && app(\App\Services\LicenseService::class)->moduleEnabled('partner_network'))<a class="underline" href="{{ route('network.dispatch.create',$c) }}">Refer to a Care Network partner</a>@endif
@include('specialist.notice')
<x-ui.card title="{{ $c->service_name }}" subtitle="{{ $c->status }} | {{ $c->mode }} | {{ $c->location }}">
<p>{{ $c->appointment->notes }}</p><p>Signed by: {{ $c->completed_name ?? 'Not signed' }}</p><p>Follow-up due: {{ $c->followup_due?->format('Y-m-d') ?? 'Not set' }}</p>
@if($c->status==='scheduled' && auth()->user()->hasPermission('specialist_consultation.update'))<form method="POST" action="{{ route('specialist.consultations.action',[$c,'start']) }}">@csrf<x-ui.button type="submit">Start consultation</x-ui.button></form>@endif
</x-ui.card>
@if($c->status!=='scheduled')<x-ui.card title="Clinical record">
@if($c->status==='in_progress' && auth()->user()->hasPermission('specialist_consultation.update'))
<form method="POST" action="{{ route('specialist.consultations.action',[$c,'clinical']) }}" class="space-y-4">@csrf<input type="hidden" name="version" value="{{ $c->version }}">
@foreach(['complaint'=>'Presenting complaint','history'=>'Clinical history / medications','allergies'=>'Allergies','conditions'=>'Existing conditions','examination'=>'Examination / vital signs','diagnosis'=>'Diagnosis / findings','plan'=>'Treatment / care plan'] as $key=>$label)
<label class="block">{{ $label }}<textarea class="mt-1 w-full rounded-md border border-med-line p-3" name="{{ $key }}" rows="3">{{ old($key,$c->clinical[$key] ?? '') }}</textarea></label>@endforeach
<x-ui.input name="followup_due" type="date" label="Follow-up due" value="{{ $c->followup_due?->format('Y-m-d') }}"/>
<x-ui.button type="submit">Save draft</x-ui.button>
@if(auth()->user()->hasPermission('specialist_consultation.complete'))<x-ui.button type="submit" name="complete" value="1">Sign and complete</x-ui.button>@endif
</form>
@else @foreach($c->clinical ?? [] as $key=>$value)<h3 class="mt-4 font-semibold">{{ Str::headline($key) }}</h3><p class="whitespace-pre-wrap">{{ $value }}</p>@endforeach @endif
</x-ui.card>@endif
@if($c->status==='completed')<x-ui.card title="Amendments">
@foreach($c->amendments as $amendment)<p class="mt-3"><strong>{{ $amendment->created_at }} / {{ $amendment->author_name }} / {{ $amendment->reason }}</strong></p><p class="whitespace-pre-wrap">{{ $amendment->content }}</p>@endforeach
@if(auth()->user()->hasPermission('specialist_consultation.amend'))<form method="POST" action="{{ route('specialist.consultations.action',[$c,'amend']) }}" class="space-y-3">@csrf<x-ui.input label="Reason for correction" name="reason" required/><label class="block">Amendment<textarea name="content" class="w-full rounded-md border p-3" required></textarea></label><x-ui.button type="submit">Add attributed amendment</x-ui.button></form>@endif
</x-ui.card>@endif
@if(in_array($c->status,['in_progress','completed']))
<x-ui.card title="Care plan revisions">@foreach($c->carePlans as $plan)<details class="border-b p-3"><summary>Version {{ $plan->version }} / {{ $plan->status }} / review {{ $plan->review_date->format('Y-m-d') }}</summary><p>{{ $plan->problem }}</p><p>{{ $plan->objectives }}</p><p class="whitespace-pre-wrap">{{ $plan->instructions }}</p></details>@endforeach
@if(auth()->user()->hasPermission('specialist_care_plan.manage'))<form method="POST" action="{{ route('specialist.consultations.action',[$c,'care-plan']) }}" class="space-y-3">@csrf<input type="hidden" name="version" value="{{ $c->carePlans->first()?->version ?? 0 }}">
@foreach(['problem','objectives','instructions'] as $field)<label class="block">{{ ucfirst($field) }}<textarea class="w-full rounded-md border p-3" name="{{ $field }}" required>{{ $c->carePlans->first()?->$field }}</textarea></label>@endforeach
<x-ui.input name="review_date" label="Review date" type="date" required/><x-ui.select name="status" label="Status"><option>active</option><option>completed</option><option>cancelled</option></x-ui.select><x-ui.button type="submit">Save new revision</x-ui.button></form>@endif</x-ui.card>
@include('specialist.orders')
@endif
@if(auth()->user()->hasPermission('specialist_billing.read'))@include('specialist.billing-panel')@endif
<x-ui.card title="Specialist history for this patient">@forelse($history as $previous)<p><a class="text-med-primary underline" href="{{ route('specialist.consultations.show',$previous) }}">Consultation #{{ $previous->id }}</a> / {{ $previous->created_at }} / {{ $previous->profile->user->name }} / {{ $previous->status }}</p>@empty<p>No earlier Specialist consultations.</p>@endforelse
<a class="text-med-primary underline" href="{{ route('specialist.patient-history',$c->patient_id) }}">Shared patient history</a>
</x-ui.card>
@if(auth()->user()->hasPermission('partner_collaboration.read') && app(\App\Services\LicenseService::class)->moduleEnabled('partner_network') && \Illuminate\Support\Facades\Schema::hasTable('partner_collaborations'))<x-ui.card title="Partner collaboration">@foreach(\App\Models\PartnerCollaboration::where('consultation_id',$c->id)->get() as $networkCase)<p><a href="{{ route('network.case',$networkCase) }}">{{ ucfirst($networkCase->kind) }}: {{ $networkCase->status }}</a></p>@endforeach</x-ui.card>@endif
</x-ui.page>
@endsection
