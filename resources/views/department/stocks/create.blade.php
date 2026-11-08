@extends('layouts.modern')

@section('title', 'Create.Blade')
@section('page-title', 'Create.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('content')

<div class="container">

<div class="d-flex justify-content-between mb-3">

<h4>
<i class="bi bi-box-arrow-in-down"></i>
Add Consumable Stock
</h4>

<a href="{{ route('department.stocks.index') }}" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back
</a>

</div>


<div class="card shadow-sm">

<div class="card-body">

<form method="POST" action="{{ route('department.stocks.store') }}">

@csrf

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">Consumable</label>

<select name="consumable_id"
class="form-control @error('consumable_id') is-invalid @enderror"
required>

<option value="">Select Consumable</option>

@foreach($consumables as $item)

<option value="{{ $item->id }}">
{{ $item->name }}
</option>

@endforeach

</select>

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Quantity</label>

<input type="number"
name="quantity"
class="form-control"
required>

</div>

</div>


<div class="col-md-3">

<div class="mb-3">

<label class="form-label">Unit Price</label>

<input type="number"
step="0.01"
name="unit_price"
class="form-control">

</div>

</div>


<div class="col-md-6">

<div class="mb-3">

<label class="form-label">Purchase Date</label>

<input type="date"
name="purchase_date"
class="form-control">

</div>

</div>


<div class="col-md-6">

<div class="mb-3">

<label class="form-label">Reference</label>

<input type="text"
name="reference"
class="form-control"
placeholder="Invoice / Supplier Ref">

</div>

</div>

</div>


<button class="btn btn-success">

<i class="bi bi-check-circle"></i>
Save Stock

</button>

</form>

</div>

</div>

</div>

@endsection
