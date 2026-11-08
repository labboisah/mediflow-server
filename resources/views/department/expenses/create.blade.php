@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() reate. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')

<div class="container">

<h4>
<i class="bi bi-cash-stack"></i>
Add Expense
</h4>

<div class="card shadow-sm">

<div class="card-body">

<form method="POST" action="{{ route('department.expenses.store') }}">

@csrf

<div class="row">

<div class="col-md-6">

<label>Category</label>

<select name="expense_category_id" class="form-control">

@foreach($categories as $category)

<option value="{{ $category->id }}">
{{ $category->name }}
</option>

@endforeach

</select>

</div>

<div class="col-md-6">

<label>Title</label>

<input type="text" name="title" class="form-control">

</div>

<div class="col-md-4">

<label>Amount</label>

<input type="number" step="0.01" name="amount" class="form-control">

</div>

<div class="col-md-4">

<label>Date</label>

<input type="date" name="expense_date" class="form-control">

</div>

<div class="col-md-12">

<label>Description</label>

<textarea name="description" class="form-control"></textarea>

</div>

</div>

<br>

<button class="btn btn-success">

<i class="bi bi-check-circle"></i>
Save Expense

</button>

</form>

</div>

</div>

</div>

@endsection
