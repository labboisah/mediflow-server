<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientReferral;
use App\Models\Appointment;
use App\Models\NextOfKin;

class PatientController extends Controller
{
     public function index() {
        return view('patient.index');
    }

    public function show(Patient $patient) {
        
        return view('patient.show', compact('patient'));
    }   

    public function history(Patient $patient) {

        $visits = $patient->visits()->paginate(10);

        return view('patient.history', compact('patient', 'visits'));
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query || strlen($query) < 2) {
            return view('patient.search', ['patients' => []]);
        }

        $patients = Patient::with('demographic')
            ->where('hospital_number', 'like', "%{$query}%")
            ->orWhereHas('demographic', function ($q) use ($query) {
                $q->where('phone_number', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->get();

        return view('patient.search', compact('patients', 'query'));
    }

}

