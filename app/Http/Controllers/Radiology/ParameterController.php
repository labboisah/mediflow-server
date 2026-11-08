<?php

namespace App\Http\Controllers\Radiology;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investigation;
use App\Models\Parameter;

class ParameterController extends Controller
{
    public function index(Investigation $investigation) {
        return view('radiology.investigation.parameter.index', compact('investigation'));
    }

    public function create(Investigation $investigation) {
        return view('radiology.investigation.parameter.create', compact('investigation'));
    }

    public function edit(Investigation $investigation, Parameter $parameter) {
        return view('radiology.investigation.parameter.edit', compact('investigation','parameter'));
    }

    public function store(Request $request, Investigation $investigation) {
        $request->validate([
            'name'=>'required',
            'unit'=>'required',
            'reference_range'=>'required',
        ]);
        $investigation->parameters()->create([
            'name'=>$request->name,
            'unit'=>$request->unit,
            'reference_range'=>$request->reference_range,
        ]);

        return redirect()->route('radiology.investigations.parameters.index',$investigation);
    }

    public function update(Request $request, Investigation $investigation, Parameter $parameter) {
        
        $request->validate([
            'name'=>'required',
        ]);

        $parameter->update([
            'name'=>$request->name,
            'unit'=>$request->unit,
            'reference_range'=>$request->reference_range,
        ]);

        return redirect()->route('radiology.investigations.parameters.index',$investigation)->with('success', 'Investigation Parameter Updated');
    }

    public function destroy(Investigation $investigation, Parameter $parameter) {
        
        if($parameter->investigationResults->count() > 0){
            return redirect()->route('radiology.investigations.parameters.index',$investigation)->with('warning', 'We cant Delete this Parameter');
        }

        $parameter->delete();

        return redirect()->route('radiology.investigations.parameters.index',$investigation)->with('success', 'Investigation Parameter Deleted');
    }
}
