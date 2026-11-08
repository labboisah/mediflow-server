<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;

class ExpenseController extends Controller
{

    public function index()
    {
        $expenses = Expense::with('category')->latest()->get();

        return view('department.expenses.index',compact('expenses'));
    }

    public function create()
    {
        $categories = ExpenseCategory::all();

        return view('department.expenses.create',compact('categories'));
    }

    public function store(Request $request)
    {

        Expense::create([
        'department_id' => auth()->user()->department_id,
        'expense_category_id' => $request->expense_category_id,
        'title' => $request->title,
        'amount' => $request->amount,
        'expense_date' => $request->expense_date,
        'description' => $request->description,
        'created_by' => auth()->id()

        ]);

        return redirect()->route('department.expenses.index')
        ->with('success','Expense recorded');

    }

    public function edit(Expense $expense)
    {

        $categories = ExpenseCategory::all();

        return view('department.expenses.edit',
        compact('expense','categories'));

    }

    public function update(Request $request, Expense $expense)
    {

        $expense->update($request->all());

        return redirect()->route('department.expenses.index');

    }

    public function destroy(Expense $expense)
    {

        $expense->delete();

        return back();

    }

}
