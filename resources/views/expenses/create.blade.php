@extends('layouts.modern')

@section('page-title','Add Expense')

@section('content')

<div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow">

<form method="POST" action="{{ route('expenses.store') }}" class="space-y-6">
@csrf

<input type="text" name="title"
       placeholder="Expense Title"
       class="w-full px-4 py-3 border rounded-xl">

<input type="number" step="0.01" name="amount"
       placeholder="Amount"
       class="w-full px-4 py-3 border rounded-xl">

<input type="date" name="expense_date"
       class="w-full px-4 py-3 border rounded-xl">

<input type="text" name="category"
       placeholder="Category (Optional)"
       class="w-full px-4 py-3 border rounded-xl">

<textarea name="description"
          placeholder="Description"
          class="w-full px-4 py-3 border rounded-xl"></textarea>

<button class="bg-accent text-white px-6 py-3 rounded-xl">
Save Expense
</button>

</form>

</div>

@endsection
