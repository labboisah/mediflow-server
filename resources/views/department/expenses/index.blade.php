@extends('layouts.modern')

@section('title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-title', ' param($m) $m.Value.ToUpper() ndex. param($m) $m.Value.ToUpper() lade')
@section('page-subtitle', 'Billing, finance, and reporting workspace.')

@section('title', 'manage expenses')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 d-flex align-items-center mb-0">
        <i class="bi bi-cash-stack me-2 text-primary"></i>
        Manage {{auth()->user()->department->name}} Expenses
    </h1>
    <a href="{{ route('department.expenses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        Add Expense
    </a>
</div>
@endsection

@section('content')


<table class="table table-striped">

<tr>
<th>Category</th>
<th>Title</th>
<th>Amount</th>
<th>Date</th>
<th>Recorded By</th>
<th>Action</th>
</tr>

@foreach($expenses as $expense)

<tr>

<td>{{ $expense->category->name }}</td>
<td>{{ $expense->title }}</td>
<td>{{ number_format($expense->amount,2) }}</td>
<td>{{ $expense->expense_date }}</td>
<td>{{ $expense->createdBy->name }}</td>

<td>

<a href="{{ route('department.expenses.edit',$expense) }}"
class="btn btn-warning btn-sm">

<i class="bi bi-pencil"></i>

</a>

<form method="POST"
action="{{ route('department.expenses.destroy',$expense) }}"
style="display:inline">

@csrf
@method('DELETE')

<button class="btn btn-danger btn-sm" onclick="return confirm('Are you sure, you want delete this expense record?')">

<i class="bi bi-trash"></i>

</button>

</form>

</td>

</tr>

@endforeach

</table>

@endsection
