<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;

class MaternalMedicationController extends Controller
{
    PUBLIC function index($patientId)
    {
        // Fetch medications for the patient
        $patient = Patient::find($patientId);
        if (!$patient) {
            return redirect()->back()->with('error', 'Patient not found.');
        }
        
        return view('midwife.medication.index', compact('patient'));
    }
}
