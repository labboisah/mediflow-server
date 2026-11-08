@extends('layouts.modern')

@section('page-title', 'Expenses')

@section('content')

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-xl font-bold text-primary">Expense Ledger</h2>

    <a href="{{ route('expenses.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg">
        + Add Expense
    </a>
</div>

<div class="bg-white p-6 rounded-xl shadow mb-6">
    <p class="text-sm text-gray-500">Today's Total Expense</p>
    <p class="text-2xl font-bold text-red-600">
        ₦{{ number_format($todayTotal,2) }}
    </p>
</div>

<div class="bg-white rounded-xl shadow p-6 overflow-x-auto">

<table class="w-full text-sm">
    <thead class="border-b bg-lightbg">
        <tr>
            <th class="py-3">Title</th>
            <th>Category</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Recorded By</th>
            <th class="text-right">Action</th>
        </tr>
    </thead>
    <tbody>
    @foreach($expenses as $expense)
        <tr class="border-b">
            <td class="py-3">{{ $expense->title }}</td>
            <td>{{ $expense->category }}</td>
            <td class="text-red-600 font-semibold">
                ₦{{ number_format($expense->amount,2) }}
            </td>
            <td>{{ $expense->expense_date }}</td>
            <td>{{ $expense->user->name }}</td>
            <td class="text-right space-x-3">
                <a href="{{ route('expenses.edit',$expense) }}"
                   class="text-blue-600">Edit</a>
                <form method="POST"
                      action="{{ route('expenses.destroy',$expense) }}"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="mt-6">
    {{ $expenses->links() }}
</div>

</div>

@endsection
