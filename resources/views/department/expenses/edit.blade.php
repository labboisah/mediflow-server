@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() dit. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('content')

<div class="container">

<h4>
<i class="bi bi-pencil"></i>
Edit Expense
</h4>

<form method="POST"
action="{{ route('department.expenses.update',$expense) }}">

@csrf
@method('PUT')

<div class="row">

<div class="col-md-6">

<label>Category</label>

<select name="expense_category_id" class="form-control">

@foreach($categories as $category)

<option value="{{ $category->id }}"
@if($expense->expense_category_id==$category->id)
selected
@endif>

{{ $category->name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-6">

<label>Title</label>

<input type="text"
name="title"
class="form-control"
value="{{ $expense->title }}">

</div>

<div class="col-md-4">

<label>Amount</label>

<input type="number"
step="0.01"
name="amount"
class="form-control"
value="{{ $expense->amount }}">

</div>

<div class="col-md-4">

<label>Date</label>

<input type="date"
name="expense_date"
class="form-control"
value="{{ $expense->expense_date }}">

</div>

<div class="col-md-12">

<label>Description</label>

<textarea name="description"
class="form-control">

{{ $expense->description }}

</textarea>

</div>

</div>

<br>

<button class="btn btn-primary">

<i class="bi bi-save"></i>
Update Expense

</button>

</form>

</div>

@endsection
