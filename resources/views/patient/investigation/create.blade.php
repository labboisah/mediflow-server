@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Send Investigation Request - ' . ($patient->demographic->full_name ?? 'Patient'))

@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-heart-pulse text-danger" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">Send Investigation Request</h1>
        <p class="mb-0 text-muted">
            Patient: <strong>{{ $patient->demographic->full_name ?? 'Unknown' }}</strong>
        </p>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Investigation Request Form</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('patient.investigation.store', $patient) }}" method="POST">
                    @csrf

                    {{-- Investigation Rows --}}
                    <div class="mb-3">
                        <label class="form-label">Investigations</label>
                        <div id="investigation-rows">
                            @php
                                $rows = old('investigation_rows', [
                                    ['investigation_type' => '', 'investigation' => '', 'specimen' => '']
                                ]);
                            @endphp

                            @foreach($rows as $index => $row)
                                <div class="investigation-row mb-3 p-3 border rounded" data-index="{{ $index }}">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">Investigation Type</label>
                                            <select name="investigation_rows[{{ $index }}][investigation_type]"
                                                    class="form-select investigation-type-select"
                                                    required>
                                                <option value="" disabled {{ empty($row['investigation_type']) ? 'selected' : '' }}>
                                                    Select investigation type
                                                </option>
                                                @foreach(App\Models\InvestigationType::all() as $type)
                                                    <option value="{{ $type->id }}"
                                                        {{ isset($row['investigation_type']) && $row['investigation_type'] == $type->id ? 'selected' : '' }}>
                                                        {{ $type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Investigation</label>
                                            <select name="investigation_rows[{{ $index }}][investigation]"
                                                    class="form-select investigation-select"
                                                    data-selected="{{ $row['investigation'] ?? '' }}"
                                                    required>
                                                <option value="" disabled {{ empty($row['investigation']) ? 'selected' : '' }}>
                                                    Select investigation
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Specimen</label>
                                            <input type="text"
                                                   name="investigation_rows[{{ $index }}][specimen]"
                                                   value="{{ old('investigation_rows.' . $index . '.specimen', $row['specimen'] ?? '') }}"
                                                   class="form-control"
                                                   placeholder="e.g. Blood, Urine">
                                        </div>

                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-row-button"
                                                    {{ $index === 0 ? 'style=visibility:hidden;' : '' }}>
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-investigation-row">
                            <i class="bi bi-plus-circle me-1"></i> Add Investigation
                        </button>
                    </div>

                    {{-- Clinical Diagnoses --}}
                    <div class="mb-3">
                        <label for="clinical_diagnoses" class="form-label">
                            Clinical Diagnoses / Additional Information
                        </label>
                        <textarea name="clinical_diagnoses"
                                  id="clinical_diagnoses"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Enter any additional information...">{{ old('clinical_diagnoses') }}</textarea>
                    </div>

                    

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('nurse.patient.show', $patient) }}"
                           class="btn btn-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- AJAX SCRIPT --}}
<script>
function getInvestigationRowTemplate(index) {
    return `
        <div class="investigation-row mb-3 p-3 border rounded" data-index="${index}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Investigation Type</label>
                    <select name="investigation_rows[${index}][investigation_type]"
                            class="form-select investigation-type-select"
                            required>
                        <option value="" disabled selected>Select investigation type</option>
                        @foreach(App\Models\InvestigationType::all() as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Investigation</label>
                    <select name="investigation_rows[${index}][investigation]"
                            class="form-select investigation-select"
                            required>
                        <option value="" disabled selected>Select investigation</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Specimen</label>
                    <input type="text"
                           name="investigation_rows[${index}][specimen]"
                           class="form-control"
                           placeholder="e.g. Blood, Urine">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-row-button">
                        &times;
                    </button>
                </div>
            </div>
        </div>
    `;
}

function loadInvestigations(typeId, selectElement, selectedInvestigationId = null) {
    if (!typeId) {
        selectElement.innerHTML = '<option value="" disabled selected>Select investigation</option>';
        return;
    }

    selectElement.innerHTML = '<option value="" disabled selected>Loading...</option>';

    fetch(`{{ url('/ajax/investigations') }}/${typeId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        selectElement.innerHTML = '<option value="" disabled selected>Select investigation</option>';

        if (!Array.isArray(data) || data.length === 0) {
            selectElement.innerHTML = '<option disabled>No investigations found</option>';
            return;
        }

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (selectedInvestigationId && selectedInvestigationId == item.id) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.innerHTML = '<option disabled>Error loading investigations</option>';
    });
}

function attachRowEvents(row) {
    const typeSelect = row.querySelector('.investigation-type-select');
    const investigationSelect = row.querySelector('.investigation-select');
    const removeButton = row.querySelector('.remove-row-button');

    typeSelect.addEventListener('change', function () {
        loadInvestigations(this.value, investigationSelect);
    });

    if (removeButton) {
        removeButton.addEventListener('click', function () {
            row.remove();
            updateRowIndices();
        });
    }
}

function updateRowIndices() {
    const rows = document.querySelectorAll('.investigation-row');
    rows.forEach((row, index) => {
        row.dataset.index = index;
        row.querySelectorAll('select, input').forEach(field => {
            const name = field.getAttribute('name');
            if (!name) return;
            const updatedName = name.replace(/investigation_rows\[\d+\]/, `investigation_rows[${index}]`);
            field.setAttribute('name', updatedName);
        });
    });
}

function initializeRows() {
    document.querySelectorAll('.investigation-row').forEach(row => {
        attachRowEvents(row);
        const typeSelect = row.querySelector('.investigation-type-select');
        const investigationSelect = row.querySelector('.investigation-select');
        const selectedInvestigation = investigationSelect.dataset.selected || null;

        if (typeSelect.value) {
            loadInvestigations(typeSelect.value, investigationSelect, selectedInvestigation);
        }
    });
}

function addInvestigationRow() {
    const container = document.getElementById('investigation-rows');
    const nextIndex = container.querySelectorAll('.investigation-row').length;
    const template = document.createElement('div');
    template.innerHTML = getInvestigationRowTemplate(nextIndex);
    const newRow = template.firstElementChild;
    container.appendChild(newRow);
    attachRowEvents(newRow);
}

document.addEventListener('DOMContentLoaded', function () {
    initializeRows();

    document.getElementById('add-investigation-row').addEventListener('click', function () {
        addInvestigationRow();
    });
});
</script>
@endsection

