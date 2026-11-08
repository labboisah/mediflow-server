<?php

namespace App\Http\Controllers\Radiology;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investigation;
use App\Models\InvestigationType;

class InvestigationController extends Controller
{
    public function index()
    {
     
        return view('radiology.investigation.index');
    }

    public function create()
    {
        return view('radiology.investigation.create');
    }   
    
    public function edit($id)
    {
        $investigation = Investigation::findOrFail($id);
        return view('radiology.investigation.edit', compact('investigation'));
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

            return redirect()->route('radiology.investigations.index')->with('success', 'Investigation created successfully.');
        }
        
    }

    public function update($id)
    {
        $investigation = Investigation::findOrFail($id);

        $validatedData = request()->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
        ]);

        $investigation->update($validatedData);

        return redirect()->route('radiology.investigations.index')->with('success', 'Investigation updated successfully.');
    }

    public function destroy($id)
    {
        $investigation = Investigation::findOrFail($id);
        $investigation->delete();

        return redirect()->route('radiology.investigations.index')->with('success', 'Investigation deleted successfully.');
    }
}
