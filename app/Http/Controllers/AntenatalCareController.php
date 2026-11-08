<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\AntenatalCare;
use Illuminate\Http\Request;
use App\Models\Service;

class AntenatalCareController extends Controller
{
    /**
     * Display a listing of antenatal care records for all female patients.
     */
    public function index()
    {
        // Only allow midwives and administrators to access
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        $search = trim((string) request('q'));

        $antenatalRecords = AntenatalCare::query()
            ->with(['patient.demographic', 'recordedBy', 'visit'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('created_at')
            ->get();

        return view('midwife.antenatal.index', compact('antenatalRecords', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('status', 'like', $like)
                ->orWhereHas('patient', function ($patientQuery) use ($like) {
                    $patientQuery
                        ->where('hospital_number', 'like', $like)
                        ->orWhereHas('demographic', function ($demographicQuery) use ($like) {
                            $demographicQuery
                                ->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('phone_number', 'like', $like);
                        });
                });
        });
    }

    /**
     * Show the form for creating a new antenatal care record.
     */
    public function create(Patient $patient)
    {
        // Authorization
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Check if patient is female and of reproductive age
        $patient->load('demographic');
        if ($patient->demographic->gender !== 'Female') {
            return redirect()->back()->with('error', 'Antenatal care can only be created for female patients');
        }

        

        // Get or create a visit for this patient
        $visit = $patient->currentVisit() ?? $patient->patientVisits()->firstOrCreate([
            'visit_date' => now(),
            'visit_type' => 'Antenatal Care',
            'created_by' => auth()->user()->id
        ]);

        return view('midwife.antenatal.create', compact('patient', 'visit'));
    }

    /**
     * Store a newly created antenatal care record in storage.
     */
    public function store(Request $request, Patient $patient)
    {
        // Authorization
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Validate the input
        $validated = $request->validate([
            'patient_visit_id' => 'required|exists:patient_visits,id',
            'last_menstrual_period' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date|after:last_menstrual_period',
            'gestational_weeks' => 'nullable|integer|min:1|max:42',
            'number_of_fetuses' => 'nullable|integer|min:1|max:8',
            'pregnancy_type' => 'nullable|string|max:50',
            'blood_pressure' => 'nullable|string|max:20',
            'weight' => 'nullable|numeric|min:30|max:250',
            'height' => 'nullable|numeric|min:100|max:250',
            'abdominal_examination' => 'nullable|string',
            'fundal_height' => 'nullable|string|max:20',
            'fetal_heart_rate' => 'nullable|string|max:20',
            'fetal_movement' => 'nullable|string',
            'vaginal_examination' => 'nullable|string',
            'urine_analysis' => 'nullable|string',
            'blood_tests' => 'nullable|string',
            'ultrasound_findings' => 'nullable|string',
            'risk_factors' => 'nullable|string',
            'complications' => 'nullable|string',
            'management_plan' => 'nullable|string',
            'counseling_topics' => 'nullable|string',
            'took_supplements' => 'nullable|boolean',
            'clinical_notes' => 'nullable|string',
            'status' => 'nullable|in:normal,complicated,high_risk',
        ]);
        $validated['status'] = $validated['status'] ?? 'normal';

        // Add recorded_by user ID
        $validated['recorded_by'] = auth()->user()->id;
        $validated['patient_id'] = $patient->id;

        // Create antenatal care record
        $antenatalCare = AntenatalCare::create($validated);

        // Log activity
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Antenatal care record created",
            'recorded_by' => auth()->id(),
        ]);
       

        return redirect()->route('midwife.antenatal.show', $antenatalCare->id)
                       ->with('success', 'Antenatal care record created successfully');
    }

    /**
     * Display the specified antenatal care record.
     */
    public function show(AntenatalCare $antenatalCare)
    {
        // Load related data
        $antenatalCare->load('patient.demographic', 'visit', 'recordedBy');

        // Authorization check
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator']) && auth()->user()->id !== $antenatalCare->recorded_by) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        return view('midwife.antenatal.show', compact('antenatalCare'));
    }

    /**
     * Show the form for editing the specified antenatal care record.
     */
    public function edit(AntenatalCare $antenatalCare)
    {
        // Authorization
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        $antenatalCare->load('patient.demographic', 'visit');

        return view('midwife.antenatal.edit', compact('antenatalCare'));
    }

    /**
     * Update the specified antenatal care record in storage.
     */
    public function update(Request $request, AntenatalCare $antenatalCare)
    {
        // Authorization
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Validate the input
        $validated = $request->validate([
            'last_menstrual_period' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'gestational_weeks' => 'nullable|integer|min:1|max:42',
            'number_of_fetuses' => 'nullable|integer|min:1|max:8',
            'pregnancy_type' => 'nullable|string|max:50',
            'blood_pressure' => 'nullable|string|max:20',
            'weight' => 'nullable|numeric|min:30|max:250',
            'height' => 'nullable|numeric|min:100|max:250',
            'abdominal_examination' => 'nullable|string',
            'fundal_height' => 'nullable|string|max:20',
            'fetal_heart_rate' => 'nullable|string|max:20',
            'fetal_movement' => 'nullable|string',
            'vaginal_examination' => 'nullable|string',
            'urine_analysis' => 'nullable|string',
            'blood_tests' => 'nullable|string',
            'ultrasound_findings' => 'nullable|string',
            'risk_factors' => 'nullable|string',
            'complications' => 'nullable|string',
            'management_plan' => 'nullable|string',
            'counseling_topics' => 'nullable|string',
            'took_supplements' => 'nullable|boolean',
            'clinical_notes' => 'nullable|string',
            'status' => 'nullable|in:normal,complicated,high_risk',
        ]);
        $validated['status'] = $validated['status'] ?? $antenatalCare->status ?? 'normal';

        // Update the record
        $antenatalCare->update($validated);

        // Log activity
        $patient = $antenatalCare->patient;
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Antenatal care record updated",
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('midwife.antenatal.show', $antenatalCare->id)
                       ->with('success', 'Antenatal care record updated successfully');
    }

    /**
     * Remove the specified antenatal care record from storage.
     */
    public function destroy(AntenatalCare $antenatalCare)
    {
        // Authorization
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Log activity before deletion
        $patient = $antenatalCare->patient;
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Antenatal care record deleted",
            'recorded_by' => auth()->id(),
        ]);

        // Soft delete
        $antenatalCare->delete();

        return redirect()->route('patient.show', $patient)
                       ->with('success', 'Antenatal care record deleted successfully');
    }

    /**
     * Display antenatal care records for a specific patient.
     */
    public function patientRecords(Patient $patient)
    {
        // Authorization
        $patient->load('demographic');
        
        if (!auth()->user()->hasAnyRole(['midwife', 'administrator'])) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Check if patient is female
        if ($patient->demographic->gender !== 'Female') {
            return redirect()->back()->with('error', 'Patient is not female');
        }

        // Get all antenatal care records
        $antenatalRecords = $patient->antenatalCares()
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return view('midwife.antenatal.patient-records', compact('patient', 'antenatalRecords'));
    }
}
