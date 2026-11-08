<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\Patient;
use App\Models\Service;
use App\Models\PatientVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabourController extends Controller
{
    

    /**
     * Display a listing of patients with labour records
     */
    public function index()
    {
        $search = trim((string) request('q'));

        $labours = Labour::query()
            ->with(['patient.demographic', 'recordedBy', 'visit'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('created_at')
            ->get();
        

        return view('midwife.labour.index', compact('labours', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('status', 'like', $like)
                ->orWhere('stage', 'like', $like)
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
     * Show the form for creating a new labour record
     */
    public function create(Patient $patient)
    {
        // Validate patient is female and reproductive age
        $age = now()->diffInYears($patient->demographic->date_of_birth);
        
        

        // Get or create current visit
        $currentVisit = $patient->currentVisit()
                               ;

        if (!$currentVisit || $currentVisit->visit_type != 'Labour') {
            foreach($patient->patientVisits as $patientVisit){
                $patientVisit->update(['statu'=>'Closed']);
            }
            $currentVisit = PatientVisit::create([
                'patient_id' => $patient->id,
                'visit_date' => now(),
                'visit_type' => 'Labour',
                'reason_of_visit'=>'labour',
                'clinical_note'=>'Labour Admission',
                'created_by'=>auth()->user()->id
            ]);
        }

        return view('midwife.labour.create', compact('patient', 'currentVisit'));
    }

    /**
     * Store a newly created labour record in storage
     */
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Labour Information
            |--------------------------------------------------------------------------
            */

            'labour_onset_time' => 'nullable|date',

            'mode_of_onset' => [
                'nullable',
                'in:spontaneous,induced',
            ],

            'reason_for_induction' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'gestational_weeks' => [
                'nullable',
                'integer',
                'min:20',
                'max:45',
            ],

            'labour_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'previous_obstetric_history' => [
                'nullable',
                'string',
                'max:3000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pre-Labour Assessment
            |--------------------------------------------------------------------------
            */

            'cervical_state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'show' => [
                'nullable',
                'in:present,absent',
            ],

            'rupture_of_membranes' => [
                'nullable',
                'in:intact,spontaneous rupture,artificial rupture',
            ],

            'liquor' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Maternal Vital Signs
            |--------------------------------------------------------------------------
            */

            'blood_pressure' => [
                'nullable',
                'string',
                'max:20',
            ],

            'pulse_rate' => [
                'nullable',
                'integer',
                'min:30',
                'max:250',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'min:30',
                'max:45',
            ],

            'respiration_rate' => [
                'nullable',
                'integer',
                'min:5',
                'max:60',
            ],

            /*
            |--------------------------------------------------------------------------
            | Labour Progress
            |--------------------------------------------------------------------------
            */

            'stage' => [
                'nullable',
                'in:not_started,first_stage,second_stage,third_stage,completed',
            ],

            'first_stage_started_at' => [
                'nullable',
                'date',
            ],

            'second_stage_started_at' => [
                'nullable',
                'date',
            ],

            'third_stage_started_at' => [
                'nullable',
                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Fetal Monitoring
            |--------------------------------------------------------------------------
            */

            'fetal_heart_rate' => [
                'nullable',
                'integer',
                'min:60',
                'max:220',
            ],

            'fetal_monitoring_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Complications & Notes
            |--------------------------------------------------------------------------
            */

            'complications' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'clinical_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Labour Status
            |--------------------------------------------------------------------------
            */

            'status' => [
                'nullable',
                'in:ongoing,completed,complicated',
            ],

        ]);

        $validated['labour_onset_time'] = $validated['labour_onset_time'] ?? now();
        $validated['stage'] = $validated['stage'] ?? 'not_started';
        $validated['status'] = $validated['status'] ?? 'ongoing';
        // get last admission for this patient
        $visit = $patient->currentVisit();

        if($visit->visit_type != 'Labour'){
            foreach($patient->patientVisits as $patientVisit){
                $patientVisit->update(['status'=>'Closed']);
            }
            $visit = PatientVisit::create([
                'patient_id' => $patient->id,
                'visit_date' => now(),
                'visit_type' => 'Labour',
                'reason_of_visit'=>'labour',
                'clinical_note'=>'Labour Admission',
                'created_by'=>auth()->user()->id
            ]);
        }

        $validated['admission_id'] = null;
        $validated['patient_id'] = $patient->id;
        $validated['patient_visit_id'] = $visit->id;
        $validated['recorded_by'] = Auth::id();

        $labour = Labour::create($validated);
        // Log activity
        $labour->patient->currentVisit()->visitActivities()->create([
            'activity' => "Labour record created with status: {$labour->status}",
            'recorded_by' => auth()->id(),
        ]);

        return redirect()->route('midwife.labour.show', $labour)
                       ->with('success', 'Labour record created successfully.');
    }

    /**
     * Display the specified labour record
     */
    public function show(Labour $labour)
    {
        $labour->load('patient.demographic', 'recordedBy');
        return view('midwife.labour.show', compact('labour'));
    }

    /**
     * Show the form for editing the specified labour record
     */
    public function edit(Labour $labour)
    {
        $labour->load('patient.demographic');
        $patient = $labour->patient;
        
        return view('midwife.labour.edit', compact('labour', 'patient'));
    }

    /**
     * Update the specified labour record in storage
     */
    public function update(Request $request, Labour $labour)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Labour Information
            |--------------------------------------------------------------------------
            */

            'labour_onset_time' => 'nullable|date',

            'mode_of_onset' => [
                'nullable',
                'in:spontaneous,induced',
            ],

            'reason_for_induction' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'gestational_weeks' => [
                'nullable',
                'integer',
                'min:20',
                'max:45',
            ],

            'labour_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'previous_obstetric_history' => [
                'nullable',
                'string',
                'max:3000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pre-Labour Assessment
            |--------------------------------------------------------------------------
            */

            'cervical_state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'show' => [
                'nullable',
                'in:present,absent',
            ],

            'rupture_of_membranes' => [
                'nullable',
                'in:intact,spontaneous rupture,artificial rupture',
            ],

            'liquor' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Maternal Vital Signs
            |--------------------------------------------------------------------------
            */

            'blood_pressure' => [
                'nullable',
                'string',
                'max:20',
            ],

            'pulse_rate' => [
                'nullable',
                'integer',
                'min:30',
                'max:250',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'min:30',
                'max:45',
            ],

            'respiration_rate' => [
                'nullable',
                'integer',
                'min:5',
                'max:60',
            ],

            /*
            |--------------------------------------------------------------------------
            | Labour Progress
            |--------------------------------------------------------------------------
            */

            'stage' => [
                'nullable',
                'in:not_started,first_stage,second_stage,third_stage,completed',
            ],

            'first_stage_started_at' => [
                'nullable',
                'date',
            ],

            'second_stage_started_at' => [
                'nullable',
                'date',
            ],

            'third_stage_started_at' => [
                'nullable',
                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Fetal Monitoring
            |--------------------------------------------------------------------------
            */

            'fetal_heart_rate' => [
                'nullable',
                'integer',
                'min:60',
                'max:220',
            ],

            'fetal_monitoring_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Complications & Notes
            |--------------------------------------------------------------------------
            */

            'complications' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'clinical_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Labour Status
            |--------------------------------------------------------------------------
            */

            'status' => [
                'nullable',
                'in:ongoing,completed,complicated',
            ],

        ]);
        
        $labour->update($validated);



        return redirect()->route('midwife.labour.show', $labour)
                       ->with('success', 'Labour record updated successfully.');
    }

    /**
     * Remove the specified labour record from storage (soft delete)
     */
    public function destroy(Labour $labour)
    {
        $patient = $labour->patient;
        
        $labour->delete();


        return redirect()->route('patient.show', $patient)
                       ->with('success', 'Labour record deleted successfully.');
    }

    /**
     * Display all labour records for a specific patient
     */
    public function patientRecords(Patient $patient)
    {
        // Validate patient is female and reproductive age
        $age = $patient->age();
        
        if ($patient->demographic->gender !== 'Female' || $age < 13 || $age > 55) {
            return redirect()->route('midwife.labour.index')
                           ->with('error', 'Labour records are only available for female patients aged 13-55 years.');
        }

        $labours = $patient->labours()
                          ->orderBy('created_at', 'desc')
                          ->get();

        return view('midwife.labour.patient-records', compact('patient', 'labours'));
    }
}
