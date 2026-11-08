<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admission;

class DischargeController extends Controller
{
    public function create(Admission $admission) {
        return view('patient.discharge.create',compact('admission'));
    }

    public function store(Request $request, Admission $admission) {
        $request->validate([
            'reason'=>'required',
            'date'=>'required',
            'time'=>'required',
        ]);

        $admission->discharge()->create([
            'reason'=>$request->reason,
            'date'=>$request->date,
            'time'=>$request->time,
            'next_appointment_date'=>$request->next_appointment_date,
            'discharge_by'=>auth()->user()->id,
        ]);

        $admission->update(['status'=>'discharged']);
        $admission->patientVisit->update(['status'=>'discharged']);
        $admission->loadMissing('bed');
        $admission->releaseBedIfNoActiveAdmission();
        // Log activity
        $admission->patientVisit->visitActivities()->create([
            'activity' => "Patient discharged with reason: {$request->reason}",
            'recorded_by' => auth()->id(),
        ]);
        
        return redirect()->route('patient.show', $admission->patientVisit->patient)
        ->with('success', 'Admission discharged successfully');

    }
}
