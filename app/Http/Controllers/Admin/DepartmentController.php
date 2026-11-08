<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\InstallationCatalog;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('admin.departments.index', [
            'departments' => app(InstallationCatalog::class)->departments()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function edit($departmentId)
    {
        return view('admin.departments.edit', [
            'department' => app(InstallationCatalog::class)->departments()->findOrFail($departmentId),
        ]);
    }

    public function show(Department $department)
    {
        $routeName = request()->route()?->getName() ?? '';
        $prefix = str_starts_with($routeName, 'medical-director.') ? 'medical-director' : 'admin';

        return redirect()->route("{$prefix}.departments.edit", $department);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        abort_unless(app(InstallationCatalog::class)->departmentAllowed($request->name), 403);

        Department::firstOrCreate(['name' => $request->name]);

        return redirect()->route('admin.departments.index')->with('success', 'Department Registered');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255']);
        abort_unless(app(InstallationCatalog::class)->departmentAllowed($request->name), 403);

        abort_unless(app(InstallationCatalog::class)->departments()->whereKey($department->id)->exists(), 403);
        $department->update(['name' => $request->name]);

        return redirect()->route('admin.departments.index')->with('success', 'Department Updated');
    }

    public function destroy(Department $department)
    {

        abort_unless(app(InstallationCatalog::class)->departments()->whereKey($department->id)->exists(), 403);
        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department Deleted');
    }
}
