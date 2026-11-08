@extends('layouts.modern')

@section('title', 'Send Laboratory Results')

@section('content')
    <x-ui.page title="Send Combined Investigation Results" subtitle="Patient: {{ $patientName }}{{ $hospitalNumber ? ' | '.$hospitalNumber : ' | Walk-in Patient' }}">
        <x-slot:actions><a href="{{ route('lab.requests.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-arrow-left"></i>Requests</a></x-slot:actions>
        <form action="{{ route('lab.requests.results.store', ['groupType' => $groupType, 'groupId' => $groupId]) }}" method="POST" class="space-y-6">
            @csrf
            @foreach($investigationRequests as $investigationRequest)
                <x-ui.card title="{{ $investigationRequest->investigation->name }}" subtitle="Requested on: {{ $investigationRequest->created_at->format('d M Y, h:i A') }}">
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($investigationRequest->investigation->parameters as $parameter)
                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-med-ink">{{ $parameter->name }}</span>
                                <input type="text" name="parameters[{{ $investigationRequest->id }}][{{ $parameter->id }}]" value="{{ old('parameters.' . $investigationRequest->id . '.' . $parameter->id) }}" placeholder="Enter result for {{ $parameter->name }} - {{ $parameter->unit }}" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm placeholder:text-med-muted/70">
                                <span class="mt-1 block text-xs text-med-muted">Reference Range: {{ $parameter->reference_range }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach
            <div class="flex flex-wrap justify-end gap-2"><a href="{{ route('lab.requests.index') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-4 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a><x-ui.button type="submit"><i class="bi bi-check-circle"></i>Submit Combined Results</x-ui.button></div>
        </form>
    </x-ui.page>
@endsection
