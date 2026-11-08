<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Newborn;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NewbornController extends Controller
{
    
    public function index()
    {
        $search = trim((string) request('q'));

        $newborns = Newborn::query()
            ->with(['patient.demographic', 'delivery', 'recordedBy'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('birth_date_time')
            ->get();

        return view('midwife.newborn.index', compact('newborns', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('status', 'like', $like)
                ->orWhere('newborn_registration_number', 'like', $like)
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

    public function create(Delivery $delivery)
    {
        if ($delivery->patient->demographic->gender !== 'Female') {
            return redirect()->route('midwife.delivery.index')
                ->with('error', 'Newborns can only be registered for female patients.');
        }

        return view('midwife.newborn.create', compact('delivery'));
    }

    public function store(Request $request, Delivery $delivery)
{
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Newborn Information
        |--------------------------------------------------------------------------
        */

        'sex' => [
            'nullable',
            'in:male,female',
        ],

        'birth_order' => [
            'nullable',
            'integer',
            'min:1',
            'max:10',
        ],

        'birth_date_time' => [
            'nullable',
            'date',
        ],

        'presentation' => [
            'nullable',
            'in:cephalic,breech,transverse,face',
        ],

        'delivery_notes' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Anthropometric Measurements
        |--------------------------------------------------------------------------
        */

        'birth_weight' => [
            'nullable',
            'string',
            'max:50',
        ],

        'birth_length' => [
            'nullable',
            'string',
            'max:50',
        ],

        'head_circumference' => [
            'nullable',
            'string',
            'max:50',
        ],

        /*
        |--------------------------------------------------------------------------
        | APGAR Scores
        |--------------------------------------------------------------------------
        */

        'apgar_score_1_minute' => [
            'nullable',
            'integer',
            'min:0',
            'max:10',
        ],

        'apgar_score_5_minutes' => [
            'nullable',
            'integer',
            'min:0',
            'max:10',
        ],

        'apgar_score_10_minutes' => [
            'nullable',
            'integer',
            'min:0',
            'max:10',
        ],

        /*
        |--------------------------------------------------------------------------
        | APGAR Components (1 Minute)
        |--------------------------------------------------------------------------
        */

        'apgar_appearance_1min' => [
            'nullable',
            'integer',
            'min:0',
            'max:2',
        ],

        'apgar_pulse_1min' => [
            'nullable',
            'integer',
            'min:0',
            'max:2',
        ],

        'apgar_grimace_1min' => [
            'nullable',
            'integer',
            'min:0',
            'max:2',
        ],

        'apgar_activity_1min' => [
            'nullable',
            'integer',
            'min:0',
            'max:2',
        ],

        'apgar_respiration_1min' => [
            'nullable',
            'integer',
            'min:0',
            'max:2',
        ],

        /*
        |--------------------------------------------------------------------------
        | Newborn Condition
        |--------------------------------------------------------------------------
        */

        'general_condition' => [
            'nullable',
            'string',
            'max:255',
        ],

        'physical_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'birth_defects_noted' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'meconium_aspiration' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Feeding & Care
        |--------------------------------------------------------------------------
        */

        'breastfeeding_initiated' => [
            'nullable',
            'boolean',
        ],

        'first_breastfeed_time' => [
            'nullable',
            'date',
        ],

        'feeding_problems' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Early Newborn Care
        |--------------------------------------------------------------------------
        */

        'vitamin_k_given' => [
            'nullable',
            'boolean',
        ],

        'eye_prophylaxis_given' => [
            'nullable',
            'boolean',
        ],

        'immunizations_given' => [
            'nullable',
            'boolean',
        ],

        'immunizations_details' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Screening Tests
        |--------------------------------------------------------------------------
        */

        'screening_test_done' => [
            'nullable',
            'boolean',
        ],

        'screening_test_results' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Special Interventions
        |--------------------------------------------------------------------------
        */

        'special_care_needed' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'referred_to' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Status & Observations
        |--------------------------------------------------------------------------
        */

        'status' => [
            'nullable',
            'in:alive,stillborn,early_neonatal_death',
        ],

        'neonatal_observations' => [
            'nullable',
            'string',
            'max:5000',
        ],

    ]);
    $validated['sex'] = $validated['sex'] ?? 'female';
    $validated['birth_date_time'] = $validated['birth_date_time'] ?? now();
    $validated['status'] = $validated['status'] ?? 'alive';

    /*
    |--------------------------------------------------------------------------
    | Create Newborn Record
    |--------------------------------------------------------------------------
    */

    $newborn = Newborn::create([

        'delivery_id' => $delivery->id,

        'patient_id' => $delivery->patient_id,

        'patient_visit_id' => $delivery->patient_visit_id ?? $delivery->patient?->currentVisit()?->id,

        'recorded_by' => auth()->id(),

        /*
        |--------------------------------------------------------------------------
        | Newborn Information
        |--------------------------------------------------------------------------
        */

        'sex' => $validated['sex'],

        'birth_order' => $validated['birth_order'] ?? 1,

        'newborn_registration_number'
            => Newborn::generateRegistrationNumber(),

        'birth_date_time' => $validated['birth_date_time'],

        'presentation' => $validated['presentation'] ?? null,

        'delivery_notes' => $validated['delivery_notes'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Anthropometric Measurements
        |--------------------------------------------------------------------------
        */

        'birth_weight' => $validated['birth_weight'] ?? null,

        'birth_length' => $validated['birth_length'] ?? null,

        'head_circumference'
            => $validated['head_circumference'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | APGAR Scores
        |--------------------------------------------------------------------------
        */

        'apgar_score_1_minute'
            => $validated['apgar_score_1_minute'] ?? null,

        'apgar_score_5_minutes'
            => $validated['apgar_score_5_minutes'] ?? null,

        'apgar_score_10_minutes'
            => $validated['apgar_score_10_minutes'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | APGAR Components
        |--------------------------------------------------------------------------
        */

        'apgar_appearance_1min'
            => $validated['apgar_appearance_1min'] ?? null,

        'apgar_pulse_1min'
            => $validated['apgar_pulse_1min'] ?? null,

        'apgar_grimace_1min'
            => $validated['apgar_grimace_1min'] ?? null,

        'apgar_activity_1min'
            => $validated['apgar_activity_1min'] ?? null,

        'apgar_respiration_1min'
            => $validated['apgar_respiration_1min'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Newborn Condition
        |--------------------------------------------------------------------------
        */

        'general_condition'
            => $validated['general_condition'] ?? null,

        'physical_examination'
            => $validated['physical_examination'] ?? null,

        'birth_defects_noted'
            => $validated['birth_defects_noted'] ?? null,

        'meconium_aspiration'
            => $validated['meconium_aspiration'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Feeding & Care
        |--------------------------------------------------------------------------
        */

        'breastfeeding_initiated'
            => $request->boolean('breastfeeding_initiated'),

        'first_breastfeed_time'
            => $validated['first_breastfeed_time'] ?? null,

        'feeding_problems'
            => $validated['feeding_problems'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Early Newborn Care
        |--------------------------------------------------------------------------
        */

        'vitamin_k_given'
            => $request->boolean('vitamin_k_given'),

        'eye_prophylaxis_given'
            => $request->boolean('eye_prophylaxis_given'),

        'immunizations_given'
            => $request->boolean('immunizations_given'),

        'immunizations_details'
            => $validated['immunizations_details'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Screening Tests
        |--------------------------------------------------------------------------
        */

        'screening_test_done'
            => $request->boolean('screening_test_done'),

        'screening_test_results'
            => $validated['screening_test_results'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Special Interventions
        |--------------------------------------------------------------------------
        */

        'special_care_needed'
            => $validated['special_care_needed'] ?? null,

        'referred_to'
            => $validated['referred_to'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Status & Outcome
        |--------------------------------------------------------------------------
        */

        'status' => $validated['status'],

        'neonatal_observations'
            => $validated['neonatal_observations'] ?? null,

    ]);
    // Log activity
    $newborn->patient->currentVisit()->visitActivities()->create([
        'activity' => "Newborn registered with status: {$newborn->status}",
        'recorded_by' => auth()->id(),
    ]);
    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('midwife.newborn.show', $newborn)
        ->with('success', 'Newborn registered successfully.');
}

    public function show(Newborn $newborn)
    {
        $newborn->load('delivery.patient.demographic', 'recordedBy', 'examinations');

        return view('midwife.newborn.show', compact('newborn'));
    }

    public function edit(Newborn $newborn)
    {
        return view('midwife.newborn.edit', compact('newborn'));
    }

    public function update(Request $request, Newborn $newborn)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Newborn Information
            |--------------------------------------------------------------------------
            */

            'sex' => [
                'nullable',
                'in:male,female',
            ],

            'birth_order' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],

            'newborn_registration_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('newborns', 'newborn_registration_number')
                    ->ignore($newborn->id),
            ],

            'birth_date_time' => [
                'nullable',
                'date',
            ],

            'presentation' => [
                'nullable',
                'in:cephalic,breech,transverse,face',
            ],

            'delivery_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Anthropometric Measurements
            |--------------------------------------------------------------------------
            */

            'birth_weight' => [
                'nullable',
                'string',
                'max:50',
            ],

            'birth_length' => [
                'nullable',
                'string',
                'max:50',
            ],

            'head_circumference' => [
                'nullable',
                'string',
                'max:50',
            ],

            /*
            |--------------------------------------------------------------------------
            | APGAR Scores
            |--------------------------------------------------------------------------
            */

            'apgar_score_1_minute' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],

            'apgar_score_5_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],

            'apgar_score_10_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],

            /*
            |--------------------------------------------------------------------------
            | APGAR Components
            |--------------------------------------------------------------------------
            */

            'apgar_appearance_1min' => [
                'nullable',
                'integer',
                'min:0',
                'max:2',
            ],

            'apgar_pulse_1min' => [
                'nullable',
                'integer',
                'min:0',
                'max:2',
            ],

            'apgar_grimace_1min' => [
                'nullable',
                'integer',
                'min:0',
                'max:2',
            ],

            'apgar_activity_1min' => [
                'nullable',
                'integer',
                'min:0',
                'max:2',
            ],

            'apgar_respiration_1min' => [
                'nullable',
                'integer',
                'min:0',
                'max:2',
            ],

            /*
            |--------------------------------------------------------------------------
            | Newborn Condition
            |--------------------------------------------------------------------------
            */

            'general_condition' => [
                'nullable',
                'string',
                'max:255',
            ],

            'physical_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'birth_defects_noted' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'meconium_aspiration' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Feeding & Care
            |--------------------------------------------------------------------------
            */

            'breastfeeding_initiated' => [
                'nullable',
                'boolean',
            ],

            'first_breastfeed_time' => [
                'nullable',
                'date',
            ],

            'feeding_problems' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Early Newborn Care
            |--------------------------------------------------------------------------
            */

            'vitamin_k_given' => [
                'nullable',
                'boolean',
            ],

            'eye_prophylaxis_given' => [
                'nullable',
                'boolean',
            ],

            'immunizations_given' => [
                'nullable',
                'boolean',
            ],

            'immunizations_details' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Screening Tests
            |--------------------------------------------------------------------------
            */

            'screening_test_done' => [
                'nullable',
                'boolean',
            ],

            'screening_test_results' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Special Interventions
            |--------------------------------------------------------------------------
            */

            'special_care_needed' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'referred_to' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status & Outcome
            |--------------------------------------------------------------------------
            */

            'status' => [
                'nullable',
                'in:alive,stillborn,early_neonatal_death',
            ],

            'neonatal_observations' => [
                'nullable',
                'string',
                'max:5000',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Newborn Record
        |--------------------------------------------------------------------------
        */

        $newborn->update([

            /*
            |--------------------------------------------------------------------------
            | Newborn Information
            |--------------------------------------------------------------------------
            */

            'sex' => $validated['sex'] ?? $newborn->sex ?? 'female',

            'birth_order' => $validated['birth_order'] ?? 1,

            'newborn_registration_number'
                => $validated['newborn_registration_number'] ?? null,

            'birth_date_time'
                => $validated['birth_date_time'] ?? $newborn->birth_date_time,

            'presentation'
                => $validated['presentation'] ?? null,

            'delivery_notes'
                => $validated['delivery_notes'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Anthropometric Measurements
            |--------------------------------------------------------------------------
            */

            'birth_weight'
                => $validated['birth_weight'] ?? null,

            'birth_length'
                => $validated['birth_length'] ?? null,

            'head_circumference'
                => $validated['head_circumference'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | APGAR Scores
            |--------------------------------------------------------------------------
            */

            'apgar_score_1_minute'
                => $validated['apgar_score_1_minute'] ?? null,

            'apgar_score_5_minutes'
                => $validated['apgar_score_5_minutes'] ?? null,

            'apgar_score_10_minutes'
                => $validated['apgar_score_10_minutes'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | APGAR Components
            |--------------------------------------------------------------------------
            */

            'apgar_appearance_1min'
                => $validated['apgar_appearance_1min'] ?? null,

            'apgar_pulse_1min'
                => $validated['apgar_pulse_1min'] ?? null,

            'apgar_grimace_1min'
                => $validated['apgar_grimace_1min'] ?? null,

            'apgar_activity_1min'
                => $validated['apgar_activity_1min'] ?? null,

            'apgar_respiration_1min'
                => $validated['apgar_respiration_1min'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Newborn Condition
            |--------------------------------------------------------------------------
            */

            'general_condition'
                => $validated['general_condition'] ?? null,

            'physical_examination'
                => $validated['physical_examination'] ?? null,

            'birth_defects_noted'
                => $validated['birth_defects_noted'] ?? null,

            'meconium_aspiration'
                => $validated['meconium_aspiration'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Feeding & Care
            |--------------------------------------------------------------------------
            */

            'breastfeeding_initiated'
                => $request->boolean('breastfeeding_initiated'),

            'first_breastfeed_time'
                => $validated['first_breastfeed_time'] ?? null,

            'feeding_problems'
                => $validated['feeding_problems'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Early Newborn Care
            |--------------------------------------------------------------------------
            */

            'vitamin_k_given'
                => $request->boolean('vitamin_k_given'),

            'eye_prophylaxis_given'
                => $request->boolean('eye_prophylaxis_given'),

            'immunizations_given'
                => $request->boolean('immunizations_given'),

            'immunizations_details'
                => $validated['immunizations_details'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Screening Tests
            |--------------------------------------------------------------------------
            */

            'screening_test_done'
                => $request->boolean('screening_test_done'),

            'screening_test_results'
                => $validated['screening_test_results'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Special Interventions
            |--------------------------------------------------------------------------
            */

            'special_care_needed'
                => $validated['special_care_needed'] ?? null,

            'referred_to'
                => $validated['referred_to'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Status & Outcome
            |--------------------------------------------------------------------------
            */

            'status'
                => $validated['status'] ?? $newborn->status ?? 'alive',

            'neonatal_observations'
                => $validated['neonatal_observations'] ?? null,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        // Log activity
        $newborn->patient->currentVisit()->visitActivities()->create([
            'activity' => "Newborn record updated with status: {$newborn->status}",
            'recorded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('midwife.newborn.show', $newborn)
            ->with('success', 'Newborn record updated successfully.');
    }

    public function destroy(Newborn $newborn)
    {
        $patient = $newborn->patient;

        // Log activity
        $patient->currentVisit()->visitActivities()->create([
            'activity' => "Newborn record deleted with status: {$newborn->status}",
            'recorded_by' => auth()->id(),
        ]);

        $newborn->delete();

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Newborn record deleted successfully.');
    }

    public function patientRecords(Patient $patient)
    {
        $patient->load('newborns.delivery');

        $newborns = $patient->newborns()->orderByDesc('birth_date_time')->get();

        return view('midwife.newborn.patient-records', compact('patient', 'newborns'));
    }
}
