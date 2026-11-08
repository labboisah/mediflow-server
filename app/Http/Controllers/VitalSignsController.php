<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientVitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VitalSignsController extends Controller
{
    /**
     * Show form for recording vital signs (Nurse/Doctor)
     * Can also be activated as quick action from Record Officer view
     */
    public function createForm(Patient $patient)
    {
        return view('vital_signs.create', compact('patient'));
    }

    /**
     * Store vital signs recording
     */
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'body_temperature' => 'required|numeric|between:35,42',
            'blood_pressure_systolic' => $this->bloodPressureRules($patient, 'blood_pressure_diastolic', '50,250'),
            'blood_pressure_diastolic' => $this->bloodPressureRules($patient, 'blood_pressure_systolic', '30,150'),
            'heart_rate' => 'required|numeric|between:30,200',
            'respiratory_rate' => 'required|numeric|between:10,50',
            'oxygen_saturation' => 'required|numeric|between:50,100',
            'blood_glucose' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'recorded_date' => 'required|date',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['recorded_by'] = Auth::id();

        PatientVitalSign::create($validated);

        return redirect()->route('record_officer.patients.show', $patient)
            ->with('success', 'Vital signs recorded successfully.');
    }

    private function bloodPressureRules(Patient $patient, string $pairedField, string $range): array
    {
        $dateOfBirth = $patient->demographic?->date_of_birth;
        $rules = $dateOfBirth && $dateOfBirth->age < 18
            ? ['nullable', 'required_with:' . $pairedField]
            : ['required'];

        return [...$rules, 'numeric', 'between:' . $range];
    }

    /**
     * Show vital signs history for patient
     */
    public function history(Patient $patient)
    {
        $vitalSigns = $patient->vitalSigns()
            ->latest('recorded_date')
            ->paginate(15);

        return redirect()->route('patient.history', $patient);
    }
}

