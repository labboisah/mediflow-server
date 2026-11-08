@extends('layouts.modern')

@section('title', 'Record Radiology Result')

@section('content')
    <x-ui.page title="Record Radiology Result" subtitle="{{ $investigationRequest->investigation?->name ?? 'Radiology investigation' }}">
        <form action="{{ route('radiology.requests.storeResult', $investigationRequest) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid gap-6 xl:grid-cols-12">
                <x-ui.card class="xl:col-span-8" title="Result Details" subtitle="Enter findings for each configured parameter.">
                                        <div class="space-y-4">
                        <x-ui.textarea label="Clinical Note" name="clinical_note" rows="3">{{ old('clinical_note', $investigationRequest->diagnosticReport?->clinical_note) }}</x-ui.textarea>
                        <x-ui.textarea label="Findings" name="findings" rows="7" required>{{ old('findings', $investigationRequest->diagnosticReport?->findings) }}</x-ui.textarea>
                        <x-ui.textarea label="Impression" name="impression" rows="4" required>{{ old('impression', $investigationRequest->diagnosticReport?->impression) }}</x-ui.textarea>
                        @forelse($investigationRequest->investigation->parameters as $parameter)
                            <x-ui.textarea
                                label="{{ $parameter->name }}"
                                name="parameters[{{ $parameter->id }}]"
                                id="parameter_{{ $parameter->id }}"
                                rows="5"
                                placeholder="Enter result for {{ $parameter->name }}{{ $parameter->unit ? ' in ' . $parameter->unit : '' }}"
                            >{{ old('parameters.' . $parameter->id) }}</x-ui.textarea>
                        @empty
                            <x-ui.empty-state title="No Parameters" message="Upload an image or configure parameters before entering structured results." />
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card class="xl:col-span-4" title="Attachment" subtitle="Attach an optional radiology image.">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-med-ink">Radiology Image</span>
                        <input type="file" name="result_image" id="result_image" accept="image/*" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-med-canvas file:px-3 file:py-2 file:text-sm file:font-semibold file:text-med-ink">
                        @error('result_image')
                            <span class="mt-1 block text-sm text-med-danger">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <a href="{{ route('radiology.requests.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Cancel</a>
                        <x-ui.button type="submit">Submit Result</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </form>
    </x-ui.page>
@endsection

