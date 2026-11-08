<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\RevenueSharingRule;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'radiologist_percent' => 'required|numeric|min:0|max:100',
            'radiographer_percent' => 'required|numeric|min:0|max:100',
            'staff_percent' => 'required|numeric|min:0|max:100',
            'annex_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Validate total equals 100
        $total = $request->radiologist_percent + $request->radiographer_percent + 
                $request->staff_percent + $request->annex_percent;
        
        if ($total != 100) {
            return back()->withErrors(['total' => 'Sum of all percentages must equal 100%. Current total: ' . $total . '%']);
        }

        $category = Category::create($request->only('name'));

        RevenueSharingRule::create([
            'category_id' => $category->id,
            'radiologist_percent' => $request->radiologist_percent,
            'radiographer_percent' => $request->radiographer_percent,
            'staff_percent' => $request->staff_percent,
            'annex_percent' => $request->annex_percent,
        ]);

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $category->load('revenueRule');
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|unique:categories,name,' . $category->id,
            'radiologist_percent' => 'required|numeric|min:0|max:100',
            'radiographer_percent' => 'required|numeric|min:0|max:100',
            'staff_percent' => 'required|numeric|min:0|max:100',
            'annex_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Validate total equals 100
        $total = $request->radiologist_percent + $request->radiographer_percent + 
                $request->staff_percent + $request->annex_percent;
        
        if ($total != 100) {
            return back()->withErrors(['total' => 'Sum of all percentages must equal 100%. Current total: ' . $total . '%']);
        }

        $category->update($request->only('name'));

        $rule = $category->revenueRule;
        if ($rule) {
            $rule->update([
                'radiologist_percent' => $request->radiologist_percent,
                'radiographer_percent' => $request->radiographer_percent,
                'staff_percent' => $request->staff_percent,
                'annex_percent' => $request->annex_percent,
            ]);
        } else {
            RevenueSharingRule::create([
                'category_id' => $category->id,
                'radiologist_percent' => $request->radiologist_percent,
                'radiographer_percent' => $request->radiographer_percent,
                'staff_percent' => $request->staff_percent,
                'annex_percent' => $request->annex_percent,
            ]);
        }

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}