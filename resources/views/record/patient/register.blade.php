@extends('layouts.modern')

@section('title', 'Register Patient')

@section('content')
<x-ui.page title="Register Patient" subtitle="Use the modern registration workspace to open a new patient record.">
    <x-slot:actions>
        <a href="{{ route('record.patients.register.form') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Open Registration</a>
        <a href="{{ route('record.patients.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Patient List</a>
    </x-slot:actions>

    <x-ui.card title="Registration Workspace" subtitle="The active registration flow is powered by the modern Livewire patient registration screen.">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-md border border-med-line bg-med-canvas/50 p-4"><p class="text-sm font-semibold text-med-ink">Patient details</p><p class="mt-1 text-sm text-med-muted">Capture demographics, contact details, file type, and flags.</p></div>
            <div class="rounded-md border border-med-line bg-med-canvas/50 p-4"><p class="text-sm font-semibold text-med-ink">Next of kin</p><p class="mt-1 text-sm text-med-muted">Record emergency contact information with validation.</p></div>
            <div class="rounded-md border border-med-line bg-med-canvas/50 p-4"><p class="text-sm font-semibold text-med-ink">Billing</p><p class="mt-1 text-sm text-med-muted">Preview registration billing before creating the patient.</p></div>
        </div>
    </x-ui.card>
</x-ui.page>
@endsection
