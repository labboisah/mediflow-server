<?php

namespace App\Http\Controllers;

use App\Models\ChildFollowUp;
use App\Models\Newborn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildFollowUpController extends Controller
{
    

    public function index()
    {
        $search = trim((string) request('q'));

        $childFollowUps = ChildFollowUp::query()
            ->with(['patient.demographic', 'newborn', 'recordedBy'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('follow_up_date_time')
            ->get();

        return view('midwife.child-follow-up.index', compact('childFollowUps', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('health_status', 'like', $like)
                ->orWhere('follow_up_period', 'like', $like)
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

    public function create(Newborn $newborn)
    {
        return view('midwife.child-follow-up.create', compact('newborn'));
    }

    public function record(Newborn $newborn)
    {
        $childFollowUps = $newborn->followUps()->with('recordedBy')->get();

        return view('midwife.child-follow-up.record', compact('newborn', 'childFollowUps'));
    }

    public function store(Request $request, Newborn $newborn)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Follow-up Details
            |--------------------------------------------------------------------------
            */

            'follow_up_date_time' => [
                'nullable',
                'date',
            ],

            'days_of_life' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'follow_up_period' => [
                'nullable',
                'in:day_3,day_7,day_10,day_14,6weeks,3months,6months,year1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Follow-up Location
            |--------------------------------------------------------------------------
            */

            'location' => [
                'nullable',
                'in:hospital,clinic,home,other',
            ],

            'location_details' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Feeding Assessment
            |--------------------------------------------------------------------------
            */

            'feeding_type' => [
                'nullable',
                'in:exclusive_breastfeeding,formula,mixed,complementary_feeding',
            ],

            'feeding_frequency' => [
                'nullable',
                'string',
                'max:255',
            ],

            'feeding_duration' => [
                'nullable',
                'string',
                'max:255',
            ],

            'how_baby_is_feeding' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'feeding_problems' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'latching_quality' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'suckling_pattern' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
            |--------------------------------------------------------------------------
            */

            'temperature' => [
                'nullable',
                'numeric',
                'between:30,45',
            ],

            'heart_rate' => [
                'nullable',
                'integer',
                'between:50,250',
            ],

            'respiratory_rate' => [
                'nullable',
                'integer',
                'between:10,120',
            ],

            /*
            |--------------------------------------------------------------------------
            | Growth Parameters
            |--------------------------------------------------------------------------
            */

            'weight' => [
                'nullable',
                'numeric',
                'between:0.5,50',
            ],

            'length' => [
                'nullable',
                'numeric',
                'between:10,150',
            ],

            'head_circumference' => [
                'nullable',
                'numeric',
                'between:10,80',
            ],

            'weight_percentile' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Jaundice Assessment
            |--------------------------------------------------------------------------
            */

            'jaundice_present' => [
                'nullable',
                'boolean',
            ],

            'jaundice_level' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'jaundice_management' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Immunization & Screening
            |--------------------------------------------------------------------------
            */

            'immunizations_up_to_date' => [
                'nullable',
                'boolean',
            ],

            'newborn_screening_done' => [
                'nullable',
                'boolean',
            ],

            'hearing_screening_done' => [
                'nullable',
                'boolean',
            ],

            'immunizations_given' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'immunizations_planned' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Clinical Summary & Plan
            |--------------------------------------------------------------------------
            */

            'clinical_summary' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'health_status' => [
                'nullable',
                'in:normal,at_risk,needs_referral,referred',
            ],

            'management_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'next_follow_up_date' => [
                'nullable',
                'date',
            ],

            'danger_signs_explained' => [
                'nullable',
                'boolean',
            ],

            'referral_reason' => [
                'nullable',
                'string',
                'max:5000',
            ],

    ]);
        $validated['follow_up_date_time'] = $validated['follow_up_date_time'] ?? now();
        $validated['location'] = $validated['location'] ?? 'hospital';
        $validated['health_status'] = $validated['health_status'] ?? 'normal';

        /*
        |--------------------------------------------------------------------------
        | Store Child Follow-up
        |--------------------------------------------------------------------------
        */

        $childFollowUp = ChildFollowUp::create([

            'newborn_id' => $newborn->id,

            'patient_id' => $newborn->patient_id,

            'patient_visit_id' => $newborn->patient_visit_id ?? $newborn->patient?->currentVisit()?->id,

            'recorded_by' => auth()->id(),

            /*
            |--------------------------------------------------------------------------
            | Follow-up Details
            |--------------------------------------------------------------------------
            */

            'follow_up_date_time'
                => $validated['follow_up_date_time'],

            'days_of_life'
                => $validated['days_of_life'] ?? null,

            'follow_up_period'
                => $validated['follow_up_period'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Follow-up Location
            |--------------------------------------------------------------------------
            */

            'location'
                => $validated['location'] ?? null,

            'location_details'
                => $validated['location_details'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Feeding Assessment
            |--------------------------------------------------------------------------
            */

            'feeding_type'
                => $validated['feeding_type'] ?? null,

            'feeding_frequency'
                => $validated['feeding_frequency'] ?? null,

            'feeding_duration'
                => $validated['feeding_duration'] ?? null,

            'how_baby_is_feeding'
                => $validated['how_baby_is_feeding'] ?? null,

            'feeding_problems'
                => $validated['feeding_problems'] ?? null,

            'latching_quality'
                => $validated['latching_quality'] ?? null,

            'suckling_pattern'
                => $validated['suckling_pattern'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
            |--------------------------------------------------------------------------
            */

            'temperature'
                => $validated['temperature'] ?? null,

            'heart_rate'
                => $validated['heart_rate'] ?? null,

            'respiratory_rate'
                => $validated['respiratory_rate'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Growth Parameters
            |--------------------------------------------------------------------------
            */

            'weight'
                => $validated['weight'] ?? null,

            'length'
                => $validated['length'] ?? null,

            'head_circumference'
                => $validated['head_circumference'] ?? null,

            'weight_percentile'
                => $validated['weight_percentile'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Jaundice Assessment
            |--------------------------------------------------------------------------
            */

            'jaundice_present'
                => $request->boolean('jaundice_present'),

            'jaundice_level'
                => $validated['jaundice_level'] ?? null,

            'jaundice_management'
                => $validated['jaundice_management'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Immunization & Screening
            |--------------------------------------------------------------------------
            */

            'immunizations_up_to_date'
                => $request->boolean('immunizations_up_to_date'),

            'newborn_screening_done'
                => $request->boolean('newborn_screening_done'),

            'hearing_screening_done'
                => $request->boolean('hearing_screening_done'),

            'immunizations_given'
                => $validated['immunizations_given'] ?? null,

            'immunizations_planned'
                => $validated['immunizations_planned'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Clinical Summary & Plan
            |--------------------------------------------------------------------------
            */

            'clinical_summary'
                => $validated['clinical_summary'] ?? null,

            'health_status'
                => $validated['health_status'],

            'management_plan'
                => $validated['management_plan'] ?? null,

            'next_follow_up_date'
                => $validated['next_follow_up_date'] ?? null,

            'danger_signs_explained'
                => $request->boolean('danger_signs_explained'),

            'referral_reason'
                => $validated['referral_reason'] ?? null,

        ]);
        // Log activity
        $newborn->patient->currentVisit()->visitActivities()->create([
            'activity' => "Child follow-up recorded for {$childFollowUp->follow_up_period}",
            'recorded_by' => auth()->id(),
        ]);
        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('midwife.child-follow-up.show', $childFollowUp)
            ->with('success', 'Child follow-up recorded successfully.');
    }

    public function show(ChildFollowUp $childFollowUp)
    {
        $childFollowUp->load('newborn.patient.demographic', 'recordedBy');

        return view('midwife.child-follow-up.show', compact('childFollowUp'));
    }

    public function edit(ChildFollowUp $childFollowUp)
    {
        return view('midwife.child-follow-up.edit', compact('childFollowUp'));
    }

    public function update(Request $request, ChildFollowUp $childFollowUp)
{
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Follow-up Details
        |--------------------------------------------------------------------------
        */

        'follow_up_date_time' => [
            'nullable',
            'date',
        ],

        'days_of_life' => [
            'nullable',
            'integer',
            'min:0',
        ],

        'follow_up_period' => [
            'nullable',
            'in:day_3,day_7,day_10,day_14,6weeks,3months,6months,year1',
        ],

        /*
        |--------------------------------------------------------------------------
        | Follow-up Location
        |--------------------------------------------------------------------------
        */

        'location' => [
            'nullable',
            'in:hospital,clinic,home,other',
        ],

        'location_details' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type' => [
            'nullable',
            'in:exclusive_breastfeeding,formula,mixed,complementary_feeding',
        ],

        'feeding_frequency' => [
            'nullable',
            'string',
            'max:255',
        ],

        'feeding_duration' => [
            'nullable',
            'string',
            'max:255',
        ],

        'how_baby_is_feeding' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'feeding_problems' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'latching_quality' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'suckling_pattern' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Vital Signs
        |--------------------------------------------------------------------------
        */

        'temperature' => [
            'nullable',
            'numeric',
            'between:30,45',
        ],

        'heart_rate' => [
            'nullable',
            'integer',
            'between:50,250',
        ],

        'respiratory_rate' => [
            'nullable',
            'integer',
            'between:10,120',
        ],

        /*
        |--------------------------------------------------------------------------
        | Growth Parameters
        |--------------------------------------------------------------------------
        */

        'weight' => [
            'nullable',
            'numeric',
            'between:0.5,50',
        ],

        'length' => [
            'nullable',
            'numeric',
            'between:10,150',
        ],

        'head_circumference' => [
            'nullable',
            'numeric',
            'between:10,80',
        ],

        'weight_percentile' => [
            'nullable',
            'numeric',
            'between:0,100',
        ],

        /*
        |--------------------------------------------------------------------------
        | Jaundice Assessment
        |--------------------------------------------------------------------------
        */

        'jaundice_present' => [
            'nullable',
            'boolean',
        ],

        'jaundice_level' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'jaundice_management' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Immunization & Screening
        |--------------------------------------------------------------------------
        */

        'immunizations_up_to_date' => [
            'nullable',
            'boolean',
        ],

        'newborn_screening_done' => [
            'nullable',
            'boolean',
        ],

        'hearing_screening_done' => [
            'nullable',
            'boolean',
        ],

        'immunizations_given' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'immunizations_planned' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Clinical Summary & Plan
        |--------------------------------------------------------------------------
        */

        'clinical_summary' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'health_status' => [
            'nullable',
            'in:normal,at_risk,needs_referral,referred',
        ],

        'management_plan' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'next_follow_up_date' => [
            'nullable',
            'date',
        ],

        'danger_signs_explained' => [
            'nullable',
            'boolean',
        ],

        'referral_reason' => [
            'nullable',
            'string',
            'max:5000',
        ],

    ]);
    $validated['follow_up_date_time'] = $validated['follow_up_date_time'] ?? $childFollowUp->follow_up_date_time ?? now();
    $validated['location'] = $validated['location'] ?? $childFollowUp->location ?? 'hospital';
    $validated['health_status'] = $validated['health_status'] ?? $childFollowUp->health_status ?? 'normal';

    /*
    |--------------------------------------------------------------------------
    | Update Child Follow-up
    |--------------------------------------------------------------------------
    */

    $childFollowUp->update([

        /*
        |--------------------------------------------------------------------------
        | Follow-up Details
        |--------------------------------------------------------------------------
        */

        'follow_up_date_time'
            => $validated['follow_up_date_time'],

        'days_of_life'
            => $validated['days_of_life'] ?? null,

        'follow_up_period'
            => $validated['follow_up_period'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Follow-up Location
        |--------------------------------------------------------------------------
        */

        'location'
            => $validated['location'] ?? null,

        'location_details'
            => $validated['location_details'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type'
            => $validated['feeding_type'] ?? null,

        'feeding_frequency'
            => $validated['feeding_frequency'] ?? null,

        'feeding_duration'
            => $validated['feeding_duration'] ?? null,

        'how_baby_is_feeding'
            => $validated['how_baby_is_feeding'] ?? null,

        'feeding_problems'
            => $validated['feeding_problems'] ?? null,

        'latching_quality'
            => $validated['latching_quality'] ?? null,

        'suckling_pattern'
            => $validated['suckling_pattern'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Vital Signs
        |--------------------------------------------------------------------------
        */

        'temperature'
            => $validated['temperature'] ?? null,

        'heart_rate'
            => $validated['heart_rate'] ?? null,

        'respiratory_rate'
            => $validated['respiratory_rate'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Growth Parameters
        |--------------------------------------------------------------------------
        */

        'weight'
            => $validated['weight'] ?? null,

        'length'
            => $validated['length'] ?? null,

        'head_circumference'
            => $validated['head_circumference'] ?? null,

        'weight_percentile'
            => $validated['weight_percentile'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Jaundice Assessment
        |--------------------------------------------------------------------------
        */

        'jaundice_present'
            => $request->boolean('jaundice_present'),

        'jaundice_level'
            => $validated['jaundice_level'] ?? null,

        'jaundice_management'
            => $validated['jaundice_management'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Immunization & Screening
        |--------------------------------------------------------------------------
        */

        'immunizations_up_to_date'
            => $request->boolean('immunizations_up_to_date'),

        'newborn_screening_done'
            => $request->boolean('newborn_screening_done'),

        'hearing_screening_done'
            => $request->boolean('hearing_screening_done'),

        'immunizations_given'
            => $validated['immunizations_given'] ?? null,

        'immunizations_planned'
            => $validated['immunizations_planned'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Clinical Summary & Plan
        |--------------------------------------------------------------------------
        */

        'clinical_summary'
            => $validated['clinical_summary'] ?? null,

        'health_status'
            => $validated['health_status'],

        'management_plan'
            => $validated['management_plan'] ?? null,

        'next_follow_up_date'
            => $validated['next_follow_up_date'] ?? null,

        'danger_signs_explained'
            => $request->boolean('danger_signs_explained'),

        'referral_reason'
            => $validated['referral_reason'] ?? null,

    ]);

    // log activity 
    $newborn = $childFollowUp->newborn;
    $newborn->patient->currentVisit()->visitActivities()->create([
        'activity' => "Child follow-up updated for {$childFollowUp->follow_up_period}",
        'recorded_by' => auth()->id(),
    ]);
    
    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('midwife.child-follow-up.show', $childFollowUp)
        ->with('success', 'Child follow-up updated successfully.');
}

    public function destroy(ChildFollowUp $childFollowUp)
    {
        $patient = $childFollowUp->patient;

        $childFollowUp->delete();

       

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Child follow-up record deleted successfully.');
    }
}
