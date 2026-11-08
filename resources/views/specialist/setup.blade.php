@extends('layouts.modern')
@section('content')
<x-ui.page title="Specialist Practice Setup" subtitle="Configure specialties, practitioners, services and approved partners. User roles and module access are assigned through User Management.">
<x-slot:actions><a href="{{ route('specialist.index') }}" class="text-med-primary underline">Workspace</a></x-slot:actions>
@include('specialist.notice')
<x-ui.card title="Workflow settings"><form method="POST" action="{{ route('specialist.setup.save','settings') }}" class="flex flex-wrap items-end gap-4">@csrf
<x-ui.select label="Operating mode" name="operating_mode">@foreach(['physical','online','hybrid'] as $mode)<option @selected(($settings?->operating_mode ?? 'hybrid')===$mode)>{{ $mode }}</option>@endforeach</x-ui.select>
<x-ui.select label="Walk-ins" name="allow_walkins"><option value="1" @selected($settings?->allow_walkins ?? true)>Allowed</option><option value="0" @selected($settings && !$settings->allow_walkins)>Disabled</option></x-ui.select><x-ui.button type="submit">Save settings</x-ui.button></form></x-ui.card>
<x-ui.card title="Patient access assignments" subtitle="Grant access only to staff involved in this patient's care or billing."><form method="POST" action="{{ route('specialist.patients.assign') }}" class="grid gap-4 md:grid-cols-3">@csrf
<x-ui.input name="hospital_number" label="Exact hospital number" required/><x-ui.select name="user_id" label="Staff member">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</x-ui.select><x-ui.button type="submit">Assign patient</x-ui.button></form></x-ui.card>
<x-ui.card title="Current patient access" subtitle="Most recent 100 assignments. Revocation applies immediately to Specialist records and private documents."><x-ui.table><thead><tr><th>Hospital number</th><th>Staff</th><th></th></tr></thead><tbody>@foreach($assignments as $assignment)<tr><td>{{ $assignment->hospital_number }}</td><td>{{ $assignment->name }}</td><td><form method="POST" action="{{ route('specialist.patients.revoke') }}">@csrf<input type="hidden" name="patient_id" value="{{ $assignment->patient_id }}"><input type="hidden" name="user_id" value="{{ $assignment->user_id }}"><x-ui.button type="submit" variant="secondary">Revoke</x-ui.button></form></td></tr>@endforeach</tbody></x-ui.table></x-ui.card>
<x-ui.card title="Specialties">
@foreach(collect([null])->concat($specialties) as $item)<details class="border-b p-3" @if(!$item) open @endif><summary>{{ $item?->name ?? 'Add specialty' }}</summary>
<form method="POST" action="{{ route('specialist.setup.save','specialty') }}" class="mt-3 grid gap-3 md:grid-cols-2">@csrf<input type="hidden" name="id" value="{{ $item?->id }}">
<x-ui.input label="Name" name="name" value="{{ $item?->name }}" required/><x-ui.input label="Description" name="description" value="{{ $item?->description }}"/>
@include('specialist.status-field',['active'=>$item?->is_active ?? true])<x-ui.button type="submit">Save specialty</x-ui.button></form></details>@endforeach
</x-ui.card>
<x-ui.card title="Specialists" subtitle="Availability uses the practitioner's timezone; unavailable dates are comma-separated YYYY-MM-DD values.">
@foreach(collect([null])->concat($profiles) as $item)<details class="border-b p-3"><summary>{{ $item?->user?->name ?? 'Add specialist' }}</summary>
<form method="POST" action="{{ route('specialist.setup.save','profile') }}" class="mt-3 grid gap-3 md:grid-cols-2">@csrf<input type="hidden" name="id" value="{{ $item?->id }}">
<x-ui.select label="Staff account" name="user_id">@foreach($users as $user)<option value="{{ $user->id }}" @selected($item?->user_id===$user->id)>{{ $user->name }}</option>@endforeach</x-ui.select>
@foreach(['title'=>'Professional title','registration'=>'Registration reference','subspecialty'=>'Subspecialty','location'=>'Physical location'] as $key=>$label)<x-ui.input label="{{ $label }}" name="{{ $key }}" value="{{ $item?->$key }}"/>@endforeach
<x-ui.input label="Timezone" name="timezone" value="{{ $item?->timezone ?? config('app.timezone','UTC') }}" required/>
@foreach(['physical'=>'Physical consultations','online'=>'Online consultations'] as $key=>$label)<x-ui.select name="{{ $key }}" label="{{ $label }}"><option value="1" @selected($item?->$key)>Enabled</option><option value="0" @selected($item && !$item->$key)>Disabled</option></x-ui.select>@endforeach
<div><p>Specialties</p>@foreach($specialties as $specialty)<label class="mr-3 inline-flex gap-2"><input type="checkbox" name="specialties[]" value="{{ $specialty->id }}" @checked($item?->specialties?->contains('id',$specialty->id))>{{ $specialty->name }}</label>@endforeach</div>
<div><p>Available days</p>@foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $key=>$day)<label class="mr-2"><input type="checkbox" name="days[]" value="{{ $key }}" @checked(in_array($key,$item?->availability['days'] ?? [1,2,3,4,5]))>{{ $day }}</label>@endforeach</div>
<x-ui.input label="From" name="from" type="time" value="{{ $item?->availability['from'] ?? '09:00' }}" required/><x-ui.input label="To" name="to" type="time" value="{{ $item?->availability['to'] ?? '17:00' }}" required/>
<x-ui.input label="Unavailable dates" name="unavailable_dates" value="{{ implode(',',$item?->availability['unavailable_dates'] ?? []) }}"/>
@include('specialist.status-field',['active'=>$item?->is_active ?? true])<x-ui.button type="submit">Save specialist</x-ui.button></form></details>@endforeach
</x-ui.card>
<x-ui.card title="Services and charges" subtitle="Charges are stored in the shared service catalogue. Bookings retain the charge agreed at booking.">
@foreach(collect([null])->concat($offerings) as $item)<details class="border-b p-3"><summary>{{ $item?->service?->name ?? 'Add service' }}</summary>
<form method="POST" action="{{ route('specialist.setup.save','service') }}" class="mt-3 grid gap-3 md:grid-cols-2">@csrf<input type="hidden" name="id" value="{{ $item?->id }}">
<x-ui.input label="Service name" name="name" value="{{ $item?->service?->name }}" required/>
<x-ui.input label="Charge (NGN)" name="price" type="number" min="0" step="0.01" value="{{ $item?->service?->price }}" required/>
<x-ui.input label="Duration (minutes)" name="duration_minutes" type="number" min="5" max="480" value="{{ $item?->duration_minutes ?? 30 }}" required/>
<x-ui.select label="Specialty" name="specialty_id">@foreach($specialties as $specialty)<option value="{{ $specialty->id }}" @selected($item?->specialty_id===$specialty->id)>{{ $specialty->name }}</option>@endforeach</x-ui.select>
<x-ui.select label="Billing department" name="department_id">@foreach($departments as $department)<option value="{{ $department->id }}" @selected($item?->service?->department_id===$department->id)>{{ $department->name }}</option>@endforeach</x-ui.select>
@include('specialist.status-field',['active'=>$item?->service?->is_active ?? true])<x-ui.button type="submit">Save service</x-ui.button></form></details>@endforeach
</x-ui.card>
<x-ui.card title="Approved external partners" subtitle="Directory entries grant no access to patient records.">
@foreach(collect([null])->concat($partners) as $item)<details class="border-b p-3"><summary>{{ $item?->name ?? 'Add partner' }}</summary>
<form method="POST" action="{{ route('specialist.setup.save','partner') }}" class="mt-3 grid gap-3 md:grid-cols-2">@csrf<input type="hidden" name="id" value="{{ $item?->id }}">
<x-ui.input label="Name" name="name" value="{{ $item?->name }}" required/><x-ui.input label="Contact" name="contact" value="{{ $item?->contact }}"/>
<x-ui.select label="Facility type" name="type">@foreach(['hospital','clinic','diagnostics','pharmacy','specialist','other'] as $type)<option @selected($item?->type===$type)>{{ $type }}</option>@endforeach</x-ui.select>
@include('specialist.status-field',['active'=>$item?->is_active ?? true])<x-ui.button type="submit">Save partner</x-ui.button></form></details>@endforeach
</x-ui.card>
<x-ui.card title="Medication catalogue" subtitle="Register a medicine for prescribing without purchasing stock."><form method="POST" action="{{ route('specialist.setup.save','medicine') }}" class="grid gap-3 md:grid-cols-3">@csrf
<x-ui.input name="name" label="Medicine name" required/><x-ui.select name="medicine_type_id" label="Type">@foreach($medicineTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</x-ui.select><x-ui.button type="submit">Add medicine</x-ui.button></form></x-ui.card>
</x-ui.page>
@endsection
