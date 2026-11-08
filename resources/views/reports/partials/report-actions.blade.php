{{-- Report Action Buttons --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Daily Activity Report - {{ $date }}</h1>
    <div>
        <a href="{{ route('reports.index') }}" class="btn btn-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="{{ route('reports.generate', ['date' => $date, 'format' => 'pdf']) }}" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </a>
        <a href="{{ route('reports.generate', ['date' => $date, 'format' => 'excel']) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Download Excel
        </a>
    </div>
</div>