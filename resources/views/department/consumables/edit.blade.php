@extends('layouts.modern')

@section('title', 'Edit.Blade')
@section('page-title', 'Edit.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('content')

<div class="container">

<div class="d-flex justify-content-between mb-3">

<h4>
<i class="bi bi-pencil"></i>
Edit Consumable
</h4>

<a href="{{ route('department.consumables.index') }}" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>

</div>

<div class="card shadow-sm">

<div class="card-body">

<form method="POST"
action="{{ route('department.consumables.update',$consumable->id) }}">

@csrf
@method('PUT')

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">Consumable Name</label>

<input type="text"
name="name"
class="form-control"
value="{{ old('name',$consumable->name) }}"
required>

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Unit</label>

<input type="text"
name="unit"
class="form-control"
value="{{ old('unit',$consumable->unit) }}">

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Reorder Level</label>

<input type="number"
name="reorder_level"
class="form-control"
value="{{ old('reorder_level',$consumable->reorder_level) }}">

</div>

</div>

</div>

<div class="mt-3">

<button class="btn btn-primary">

<i class="bi bi-save"></i>
Update Consumable

</button>

</div>

</form>

</div>

</div>

</div>

@endsection
