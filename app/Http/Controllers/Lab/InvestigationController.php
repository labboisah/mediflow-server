<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investigation;
use App\Models\InvestigationType;

class InvestigationController extends Controller
{
    public function index()
    {
        return view('lab.investigation.index');
    }

    public function create()
    {
        return view('lab.investigation.create');
    }   
    
    public function edit($id)
    {
        $investigation = Investigation::findOrFail($id);
        return view('lab.investigation.edit', compact('investigation'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
        ]);
        
        $type = InvestigationType::findOrFail(1);

        if ($type) {
            $type->investigations()->create([
                'name' => $request->name,
                'price' => $request->price,
                'code' => $request->code,
            ]);

            return redirect()->route('lab.investigations.index')->with('success', 'Investigation created successfully.');
        }
        
    }

    public function update($id)
    {
        $investigation = Investigation::findOrFail($id);

        $validatedData = request()->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
        ]);

        $investigation->update($validatedData);

        return redirect()->route('lab.investigations.index')->with('success', 'Investigation updated successfully.');
    }

    public function destroy($id)
    {
        $investigation = Investigation::findOrFail($id);
        if($investigation->investigationRequests->count() > 0){
            return redirect()->route('lab.investigations.index')->with('warning', 'We cant delete this investigation.');
        }
        $investigation->delete();

        return redirect()->route('lab.investigations.index')->with('success', 'Investigation deleted successfully.');
    }
}
