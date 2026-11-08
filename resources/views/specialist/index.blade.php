@extends('layouts.modern')
@section('content')
<x-ui.page title="Specialist Care" subtitle="Appointments, assigned patients and connected care.">
<x-slot:actions>
@if(auth()->user()->hasPermission('specialist.settings.manage'))<a class="text-med-primary underline" href="{{ route('specialist.setup') }}">Practice setup</a>@endif
@if(auth()->user()->hasPermission('specialist_report.read'))<a class="text-med-primary underline" href="{{ route('specialist.reports') }}">Reports</a>@endif
</x-slot:actions>
@include('specialist.notice')
<div class="grid gap-4 md:grid-cols-3">@foreach($counts as $label=>$value)<x-ui.card><p>{{ str($label)->replace('_',' ')->title() }}</p><strong class="text-2xl">{{ $value }}</strong></x-ui.card>@endforeach</div>
<x-ui.card title="Appointments">
<x-ui.table><thead><tr><th>Patient</th><th>Specialist</th><th>Date / local time</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($consultations as $c)<tr><td class="p-3">{{ $c->patient->name() }}<br>{{ $c->patient->hospital_number }}</td><td>{{ $c->profile->user->name }}</td><td>{{ $c->appointment->appointment_date->format('Y-m-d') }} {{ $c->appointment->appointment_time }} {{ $c->appointment->timezone }}</td><td>{{ $c->status }}</td><td>
@if(auth()->user()->hasPermission('specialist_consultation.read'))<a class="text-med-primary underline" href="{{ route('specialist.consultations.show',$c) }}">Open</a>@endif
@if(auth()->user()->hasPermission('specialist_billing.read'))<a class="text-med-primary underline" href="{{ route('specialist.billing',$c) }}">Billing</a>@endif
@if($c->status==='scheduled' && auth()->user()->hasPermission('specialist_appointment.manage'))<details><summary>Reschedule</summary><form method="POST" action="{{ route('specialist.consultations.action',[$c,'reschedule']) }}">@csrf<x-ui.input name="starts_at" type="datetime-local" label="New local time" required/><x-ui.input name="reason" label="Reason" required/><x-ui.button type="submit">Move appointment</x-ui.button></form></details><form method="POST" action="{{ route('specialist.consultations.action',[$c,'cancel']) }}">@csrf<input name="reason" placeholder="Cancellation reason" required maxlength="2000"><button class="text-med-danger">Cancel</button></form>@endif
</td></tr>@empty<tr><td colspan="5" class="p-4">No assigned appointments.</td></tr>@endforelse
</tbody></x-ui.table>{{ $consultations->links() }}</x-ui.card>
@if(auth()->user()->hasPermission('specialist_appointment.manage'))
<x-ui.card title="Book consultation" subtitle="Times use the specialist's configured timezone. Rescheduling preserves the appointment and its agreed charge.">
<form method="GET" class="mb-4 flex gap-3"><x-ui.input name="search" label="Find assigned patient" value="{{ $search }}" placeholder="Name or hospital number"/><x-ui.button type="submit">Search</x-ui.button></form>
<form method="POST" action="{{ route('specialist.appointments.store') }}" class="grid gap-4 md:grid-cols-2">@csrf<input type="hidden" name="token" value="{{ old('token', (string)Str::uuid()) }}">
<x-ui.select name="patient_id" label="Patient" required>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->name() }} ({{ $patient->hospital_number }})</option>@endforeach</x-ui.select>
<x-ui.select name="profile_id" label="Specialist" required>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->user->name }} ({{ $profile->timezone }})</option>@endforeach</x-ui.select>
<x-ui.select name="service_id" label="Service" required>@foreach($offerings as $offering)<option value="{{ $offering->service_id }}">{{ $offering->specialty->name }} / {{ $offering->service->name }} / {{ number_format($offering->service->price,2) }} / {{ $offering->duration_minutes }} minutes</option>@endforeach</x-ui.select>
<x-ui.input type="datetime-local" name="starts_at" label="Start (specialist local time)" required value="{{ old('starts_at') }}"/>
<x-ui.select name="mode" label="Mode"><option value="physical">Physical</option><option value="online">Online</option></x-ui.select>
<x-ui.input name="reason" label="Reason for visit" required maxlength="2000" value="{{ old('reason') }}"/>
<x-ui.input name="patient_visit_id" type="number" label="Existing active visit ID (optional shared encounter)"/>
<x-ui.input name="followup_id" type="number" label="Originating consultation ID (follow-up only)" value="{{ request('followup_id') }}"/>
<label><input type="checkbox" name="walkin" value="1">Walk-in</label><x-ui.button type="submit">Book</x-ui.button></form>
</x-ui.card>@endif
@if(auth()->user()->hasPermission('specialist_patient.manage'))
<x-ui.card title="Register patient" subtitle="Search for an existing hospital number first. A manager can assign existing records to your team. Registration creates the patient record; charges are collected through configured Specialist services.">
<form method="POST" action="{{ route('specialist.patients.store') }}" class="grid gap-4 md:grid-cols-2">@csrf
@foreach(['first_name'=>'First name','last_name'=>'Last name','phone_number'=>'Phone number'] as $key=>$label)<x-ui.input name="{{ $key }}" label="{{ $label }}" required value="{{ old($key) }}"/>@endforeach
<x-ui.input name="email" type="email" label="Email (optional)" value="{{ old('email') }}"/><x-ui.input name="date_of_birth" type="date" label="Date of birth" required value="{{ old('date_of_birth') }}"/>
<x-ui.select name="gender" label="Gender"><option>Male</option><option>Female</option><option>Other</option></x-ui.select>
<x-ui.select name="file_type_id" label="File type">@foreach($fileTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</x-ui.select>
<x-ui.button type="submit">Register</x-ui.button></form></x-ui.card>@endif
@if(auth()->user()->hasPermission('specialist_referral.accept'))<x-ui.card title="Incoming referrals">
@forelse($incoming as $referral)<div class="border-b p-4"><strong>{{ $referral->patient->name() }} / {{ $referral->urgency }}</strong><p>{{ $referral->reason_for_referral }}</p><p class="whitespace-pre-wrap">{{ $referral->notes }}</p>
<form method="POST" action="{{ route('specialist.referrals.respond',$referral) }}" class="space-y-3">@csrf
<x-ui.select name="status" label="Response">@if($referral->status==='Pending')<option>Accepted</option><option>Rejected</option>@else<option>Completed</option>@endif</x-ui.select>
<x-ui.input name="outcome" label="Outcome / response" required/><x-ui.input name="admission_id" type="number" label="Existing admission ID (hospital outcome only)"/>
<x-ui.button type="submit">Record response</x-ui.button></form></div>@empty<p>No incoming referrals.</p>@endforelse</x-ui.card>@endif
</x-ui.page>
@endsection
