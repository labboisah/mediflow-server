@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('content')

<div class="container">

<div class="d-flex justify-content-between mb-3">
<h4>
<i class="bi bi-box-seam"></i>
Create Consumable
</h4>

<a href="{{ route('department.consumables.index') }}" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>
</div>

<div class="card shadow-sm">

<div class="card-body">

<form method="POST" action="{{ route('department.consumables.store') }}">

@csrf

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">Consumable Name</label>

<input type="text"
name="name"
class="form-control @error('name') is-invalid @enderror"
placeholder="Enter consumable name"
value="{{ old('name') }}"
required>

@error('name')
<div class="invalid-feedback">
{{ $message }}
</div>
@enderror

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Unit</label>

<input type="text"
name="unit"
class="form-control"
placeholder="e.g box, piece, pack"
value="{{ old('unit') }}">

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Reorder Level</label>

<input type="number"
name="reorder_level"
class="form-control"
value="{{ old('reorder_level',10) }}">

</div>

</div>

</div>

<div class="mt-3">

<button class="btn btn-success">

<i class="bi bi-check-circle"></i>
Save Consumable

</button>

</div>

</form>

</div>

</div>

</div>

@endsection
