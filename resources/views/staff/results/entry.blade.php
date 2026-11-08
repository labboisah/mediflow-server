@extends('layouts.modern')

@section('page-title', 'Enter Result')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-2xl shadow">
        <div class="flex flex-wrap gap-4 justify-between">
            <div>
                <h2 class="text-2xl font-bold text-primary">Bill {{ $bill->bill_no }}</h2>
                <p class="text-sm text-gray-500">Patient: {{ $bill->patient_name ?? 'Walk-in' }}</p>
            </div>
            <div class="text-sm text-gray-600">
                <p><strong>Status:</strong> {{ strtoupper($bill->payment_status) }}</p>
                <p><strong>Total Paid:</strong> NGN {{ number_format($bill->total_paid, 2) }}</p>
                <p><strong>Bill Date:</strong> {{ $bill->created_at->format('d M Y h:i A') }}</p>
            </div>
        </div>
    </div>

    @foreach($bill->items as $item)
        @php
            $result = $item->result;
            $isRadiographer = $staffType === 'radiographer';
            $lockedByOtherReporter = $result && $result->reported_by && $result->reported_by !== auth()->id();
        @endphp

        <div class="bg-white p-6 rounded-2xl shadow">
            <div class="flex flex-wrap gap-3 justify-between mb-5">
                <div>
                    <h3 class="text-xl font-bold text-primary">{{ $item->service->name }}</h3>
                    <p class="text-sm text-gray-500">{{ optional($item->service->category)->name }}</p>
                </div>
                <div class="flex flex-col gap-2 text-right">
                    @if($result?->performed_by)
                        <span class="px-3 py-1 rounded-full text-xs bg-blue-600 text-white self-start">
                            Performed by {{ optional($result->performer)->name ?? 'Unknown staff' }}
                        </span>
                    @endif

                    @if($result?->reported_by)
                        <span class="px-3 py-1 rounded-full text-xs bg-accent text-white self-start">
                            Reported by {{ optional($result->reporter)->name ?? 'Unknown staff' }}
                        </span>
                    @endif
                </div>
            </div>

            @if($isRadiographer)
                @if($result?->performed_by && $result->performed_by !== auth()->id())
                    <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600">
                        This investigation has been marked as performed by {{ optional($result->performer)->name ?? 'another staff member' }}.
                    </div>
                @else
                    <form method="POST" action="{{ route('staff.results.store', $bill) }}">
                        @csrf
                        <input type="hidden" name="bill_item_id" value="{{ $item->id }}">

                        @if($result?->performed_by)
                            <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600 mb-4">
                                This investigation has been marked as performed by {{ optional($result->performer)->name ?? 'you' }}.
                            </div>
                        @endif

                        <button class="bg-accent text-white px-6 py-3 rounded-xl">
                            {{ $result?->performed_by === auth()->id() ? 'Update Performed Status' : 'Mark as Performed' }}
                        </button>
                    </form>
                @endif
            @elseif($lockedByOtherReporter)
                <div class="bg-lightbg p-4 rounded-xl text-sm text-gray-600">
                    This result has already been reported by {{ optional($result->reporter)->name ?? 'another staff member' }}.
                </div>
            @else
                <form method="POST" action="{{ route('staff.results.store', $bill) }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="bill_item_id" value="{{ $item->id }}">

                    <div>
                        <label class="block text-sm font-medium mb-2">Result Template</label>
                        <select class="template-select w-full px-4 py-3 border rounded-xl" data-item="{{ $item->id }}">
                            <option value="">Select saved template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Clinical Note</label>
                        <textarea id="clinical_note_{{ $item->id }}" name="clinical_note" rows="3" class="w-full px-4 py-3 border rounded-xl">{{ old('clinical_note', $result?->clinical_note) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Findings</label>
                        <textarea id="findings_{{ $item->id }}" name="findings" rows="7" class="w-full px-4 py-3 border rounded-xl" required>{{ old('findings', $result?->findings) }}</textarea>
                        @error('findings')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Impression</label>
                        <textarea id="impression_{{ $item->id }}" name="impression" rows="4" class="w-full px-4 py-3 border rounded-xl">{{ old('impression', $result?->impression) }}</textarea>
                    </div>

                    <div class="bg-lightbg p-4 rounded-xl space-y-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="save_template" value="1" class="rounded">
                            Save this result text as a template
                        </label>

                        <input type="text" name="template_name"
                               placeholder="Template name"
                               class="w-full px-4 py-3 border rounded-xl">

                        @error('template_name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button class="bg-accent text-white px-6 py-3 rounded-xl">Save and Print</button>
                        @if($result)
                            <a href="{{ route('staff.results.print', $result) }}"
                               class="bg-primary text-white px-6 py-3 rounded-xl">Print Existing</a>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
const resultTemplates = @js($templatePayload);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.template-select').forEach((select) => {
        select.addEventListener('change', () => {
            const template = resultTemplates[String(select.value)];
            const itemId = select.dataset.item;

            if (!template) {
                return;
            }

            document.getElementById(`clinical_note_${itemId}`).value = template.clinical_note || '';
            document.getElementById(`findings_${itemId}`).value = template.findings || '';
            document.getElementById(`impression_${itemId}`).value = template.impression || '';
        });
    });
});
</script>
@endsection

