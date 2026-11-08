@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Write to Continuation Sheet - ' . ($patient->demographic->full_name ?? 'Patient'))

@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-pencil text-danger" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">Write to Continuation Sheet</h1>
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
                <h5 class="mb-0">Continuation Sheet</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('patient.continuation.store', $patient) }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <textarea name="notes" id="" rows="10" class="form-control" placeholder="Write Some paragraph of continuation">
                        </textarea>
                    </div>

                    <div class="form-group">
                        <textarea name="history" id="" rows="10" class="form-control" placeholder="Write Some paragraph of history">
                        </textarea>
                    </div>

                    <div class="form-group">
                        <textarea name="examination" id="" rows="10" class="form-control" placeholder="Write Some paragraph of examination">
                        </textarea>
                    </div>

                    <div class="form-group">
                        <textarea name="diagnosis" id="" rows="10" class="form-control" placeholder="Write Some paragraph of diagnosis">
                        </textarea>
                    </div>

                    <div class="form-group">
                        <textarea name="plan" id="" rows="10" class="form-control" placeholder="Write Some paragraph of plan">
                        </textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Save 
                    </button>
                    
                </form>
            </div>
        </div>
    </div>
</div>

{{-- AJAX SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const typeSelect = document.getElementById('investigation_type');
    const investigationSelect = document.getElementById('investigation');

    const ajaxBaseUrl = "{{ url('/ajax/investigations') }}";

    typeSelect.addEventListener('change', function () {

        const typeId = this.value;

        investigationSelect.innerHTML =
            '<option value="" disabled selected>Loading...</option>';

        if (!typeId) return;

        fetch(`${ajaxBaseUrl}/${typeId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {

            investigationSelect.innerHTML =
                '<option value="" disabled selected>Select investigation</option>';

            if (data.length === 0) {
                investigationSelect.innerHTML =
                    '<option disabled>No investigations found</option>';
                return;
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                investigationSelect.appendChild(option);
            });
        })
        .catch(error => {

            console.error('Error:', error);

            investigationSelect.innerHTML =
                '<option disabled>Error loading investigations</option>';
        });
    });
});
</script>
@endsection

