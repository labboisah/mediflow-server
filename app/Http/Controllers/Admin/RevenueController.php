<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Department;

class RevenueController extends Controller
{
    public function index()
    {
        $revenues = Revenue::with(['category'])->latest()->get();

        return view('admin.revenues.index', compact('revenues'));
    }

    public function create()
    {
        $categories = RevenueCategory::all();
        $departments = Department::all();

        return view('admin.revenues.create', compact('categories', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'revenue_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();

        Revenue::create($data);

        return redirect()->route('admin.revenues.index')
            ->with('success', 'Revenue recorded successfully.');
    }

    public function edit(Revenue $revenue)
    {
        $categories = RevenueCategory::all();
        $departments = Department::all();

        return view('admin.revenues.edit', compact('revenue', 'categories', 'departments'));
    }

    public function update(Request $request, Revenue $revenue)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'revenue_category_id' => ['required', 'exists:revenue_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'revenue_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $revenue->update($data);

        return redirect()->route('admin.revenues.index')
            ->with('success', 'Revenue updated successfully.');
    }

    public function destroy(Revenue $revenue)
    {
        $revenue->delete();

        return redirect()->route('admin.revenues.index')
            ->with('success', 'Revenue deleted successfully.');
    }
}
