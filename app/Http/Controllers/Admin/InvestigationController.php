<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investigation;
use App\Models\Department;
use App\Models\InvestigationType;

class InvestigationController extends Controller
{
    public function index() {
        return view('admin.investigations.index', [
            'departments' => Department::query()
                ->with(['investigationTypes' => function ($query) {
                    $query->with(['investigations' => fn ($builder) => $builder->withCount('parameters')->orderBy('name')])
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create() {
        return view('admin.investigations.create', [
            'investigationTypes' => InvestigationType::with('department')->orderBy('name')->get(),
        ]);
    }

    public function edit($investigationId) {
        return view('admin.investigations.edit', [
            'investigation' => Investigation::with('investigationType.department')->findOrFail($investigationId),
            'investigationTypes' => InvestigationType::with('department')->orderBy('name')->get(),
        ]);
    }

    public function show(Investigation $investigation) {
        $routeName = request()->route()?->getName() ?? '';
        $prefix = str_starts_with($routeName, 'medical-director.') ? 'medical-director' : 'admin';

        return redirect()->route("{$prefix}.investigations.edit", $investigation);
    }

    public function store(Request $request) {
        $request->validate([
            'name'=>'required',
            'price'=>'required',
            'investigation_type'=>'required',
            ]);

        Investigation::firstOrCreate([
            'name'=>$request->name,
            'price'=>$request->price,
            'investigation_type_id'=>$request->investigation_type,
        ]);

        return redirect()->route('admin.investigations.index')->with('success', 'Investigation Registered');
    }

    public function update(Request $request, Investigation $investigation) {
        
        $request->validate([
            'name'=>'required',
            'price'=>'required',
            'investigation_type'=>'required',
            ]);

        $investigation->update([
            'name'=>$request->name,
            'price'=>$request->price,
            'investigation_type_id'=>$request->investigation_type,
            ]);

        return redirect()->route('admin.investigations.index')->with('success', 'Investigation Updated');
    }

    public function destroy(Investigation $investigation) {
        
        if(! $investigation->investigationRequests()->exists()){
            $investigation->delete();
            $message = 'Investigation Deleted';
        }else{
            $message = 'Investigation has request record';
        }
        

        return redirect()->route('admin.investigations.index')->with('success', $message);
    }
}
