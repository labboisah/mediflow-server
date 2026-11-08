<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

class ObservationController extends Controller
{
    public function record(Patient $patient){
        return view('patient.observation.record',compact('patient'));
    }

    public function register(Request $request, Patient $patient) {
        $validated = $request->validate([
            "temperature" => "nullable|numeric",
            "mate_pulse" => "nullable|numeric",
            "blood_pressure_systolic" => "nullable|integer",
            "blood_pressure_diastolic" => "nullable|integer",
            "respiratory_rate" => "nullable|integer",
            "drop_rate" => "nullable|integer",
            "constraction" => "nullable|string|max:255",
            "fits" => "nullable|string|max:255",
            "date" => "nullable|date",
            "time" => "nullable",
            "remark" => "nullable|string|max:10000",
        ]);
        
        $date = $validated['date'] ?? now();
        $time = $validated['time'] ?? now()->format('H:i');

        $visit = $patient->currentVisit();

        if($visit){
            $visit->observations()->create([
                "temperature" => $request->temperature,
                "mate_pulse" => $request->mate_pulse,
                "blood_pressure" => ($validated['blood_pressure_systolic'] && $validated['blood_pressure_diastolic']) ? $validated['blood_pressure_systolic'].'/'.$validated['blood_pressure_diastolic'] : null,
                "respiration" => $validated['respiratory_rate'] ?? null,
                "drop_rate" => $validated['drop_rate'] ?? null,
                "constraction" => $request->constraction,
                "fits" => $request->fits,
                "date" => $request->date,
                "time" => $request->time,
                "remark" => $request->remark,
                'recorded_by'=>auth()->user()->id
            ]);

            // Log activity
            $patient->currentVisit()->visitActivities()->create([
                'activity' => "Observation recorded",    
                'recorded_by' => auth()->id(),
            ]);

            return redirect()->route('patient.show',$patient)->with('success', 'Observation Recorded Successfully');
        }
        return redirect()->back()->with('error', 'No admission available, pls confirm the patient admission');
    }
}
