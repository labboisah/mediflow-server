<?php

namespace App\Http\Controllers\Nurse;

use App\Http\Controllers\Controller;
use App\Models\DrugChart;
use App\Models\Observation;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\VitalSign;

class NurseController extends Controller
{
    public function index()
    {
        return redirect()->route('nurse.dashboard');
    }

    public function dashboard()
    {
        $recentVisits = PatientVisit::with(['patient.demographic'])
            ->whereDate('created_at', today())
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentVitalSigns = VitalSign::with(['patientVisit.patient.demographic', 'recordedBy'])
            ->whereDate('created_at', today())
            ->latest('created_at')
            ->limit(5)
            ->get();

        $summary = [
            'vitalSignsToday' => VitalSign::whereDate('recorded_date', today())->count(),
            'observationsToday' => Observation::whereDate('created_at', today())->count(),
            'drugChartsToday' => DrugChart::whereDate('created_at', today())->count(),
            'walkInPatients' => Patient::where('is_walkIn', true)->count(),
        ];

        return view('nurse.dashboard', compact('summary', 'recentVisits', 'recentVitalSigns'));
    }
}
