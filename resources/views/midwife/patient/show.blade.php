
@php 
$patient = App\Models\Patient::find(request()->route('patient'));
@endphp

@include('patient.show')