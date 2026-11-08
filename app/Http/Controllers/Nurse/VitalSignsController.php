<?php

namespace App\Http\Controllers\Nurse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VitalSignsRequest;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitVitalSign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class VitalSignsController extends Controller
{
    public function create(VitalSignsRequest $vitalSignsRequest) {
        return view('nurse.patient.vital_signs.create', compact('vitalSignsRequest'));
    }

    public function register(Request $request, VitalSignsRequest $vitalSignsRequest)
    {
        $patient = $vitalSignsRequest->patientVisit->patient;

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
            'recorded_date' => 'required',
        ]);

       try { 
        DB::beginTransaction();
        $validated['vital_signs_request_id'] = $vitalSignsRequest->id;
        $validated['recorded_by'] = Auth::id();

        PatientVisitVitalSign::create($validated);

        $vitalSignsRequest->update(['status' => 'Completed']);
        DB::commit();
        return redirect()->route('nurse.patients.show', $patient)
        ->with('success', 'Vital signs recorded successfully.');
        } catch (\Exception $e) { 
            DB::rollBack(); 
            return back()->withErrors('An error occurred while recording vital signs: ' . $e->getMessage())->withInput(); 
        }
    }

    public function edit(PatientVisitVitalSign $patientVisitVitalSign) {
        return view('nurse.patient.vital_signs.edit', compact('patientVisitVitalSign'));
    }

    public function update(Request $request, PatientVisitVitalSign $patientVisitVitalSign) {
        $patient = $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient;

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
            'recorded_date' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $before = $patientVisitVitalSign->toArray();
            
            $patientVisitVitalSign->update($validated);

            $after = $patientVisitVitalSign->fresh()->toArray();

            AuditLog::record(auth()->user(), 'vital_signs.update', $patientVisitVitalSign, $before, $after);
            
            DB::commit();
            return redirect()->route('nurse.patients.show', $patientVisitVitalSign->vitalSignsRequest->patientVisit->patient)
                ->with('success', 'Vital signs updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('An error occurred while updating vital signs: '.$e->getMessage())->withInput();
        }
    }

    private function bloodPressureRules(Patient $patient, string $pairedField, string $range): array
    {
        $dateOfBirth = $patient->demographic?->date_of_birth;
        $rules = $dateOfBirth && $dateOfBirth->age < 18
            ? ['nullable', 'required_with:' . $pairedField]
            : ['required'];

        return [...$rules, 'numeric', 'between:' . $range];
    }

}
