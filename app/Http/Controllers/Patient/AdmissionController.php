<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Bed;

class AdmissionController extends Controller
{
    public function create(Patient $patient) {
        return view('patient.admission.create', compact('patient'));
    }

    public function store(Request $request, Patient $patient) {

        $request->validate([
            'bed_id'=>'required', 
            'date'=>'required', 
            'days'=>'required'
            ]);

        $admission = $patient->currentVisit()->admissions()->create([
            'date'=>$request->date,
            'bed_id'=>$request->bed_id,
            'note'=>$request->note,
            'time'=>$request->time,
            'admitted_by'=> auth()->user()->id
            ]);
        $bed = Bed::find($request->bed_id);
        
        $bed->update(['status'=>'occupied']);

        $patient->currentVisit()->generateBedSpaceBill($admission, $bed, $request->days);
        // Log activity        
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Patient admitted to bed {$bed->name} for {$request->days} days",
            'recorded_by' => auth()->id(),
        ]); 

        return redirect()->route('patient.show',$patient)->with('success','Admission Registerred');
    }

    public function confirmed(Admission $admission) {
        $admission->update(['status'=>'confirmed']);
        return redirect()->route('patient.show', $admission->patientVisit->patient)->with('success', 'Patient Admission Confirmed');
    }
}
