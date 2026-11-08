<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class ContinuationController extends Controller
{
    public function create(Patient $patient) {
        return view('patient.continuation.create', compact('patient'));
    }

    public function store(Request $request, Patient $patient) {
        $request->validate([
            'notes'=>'required'
        ]);

        $patient->currentVisit()->continuations()->create([
            'note'=>$request->notes,
            'history'=>$request->history,
            'examination'=>$request->examination,
            'diagnosis'=>$request->diagnosis,
            'plan'=>$request->plan,
            'written_by'=>auth()->user()->id
        ]);
        // Log activity
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Continuation sheet updated",
            'recorded_by' => auth()->id(),
        ]);
        
        return redirect()->route('patient.show',$patient)->with('success', 'Continuation Sheet Recorded');

    }
}
