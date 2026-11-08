<?php

namespace App\Http\Controllers;

use App\Models\Newborn;
use App\Models\NewbornExamination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewbornExaminationController extends Controller
{
    

    public function index()
    {
        $search = trim((string) request('q'));

        $newbornExaminations = NewbornExamination::query()
            ->with(['newborn.patient.demographic', 'recordedBy'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('examination_date_time')
            ->get();

        return view('midwife.newborn-examination.index', compact('newbornExaminations', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('exam_status', 'like', $like)
                ->orWhereHas('newborn', function ($newbornQuery) use ($like) {
                    $newbornQuery
                        ->where('newborn_registration_number', 'like', $like)
                        ->orWhereHas('patient', function ($patientQuery) use ($like) {
                            $patientQuery
                                ->where('hospital_number', 'like', $like)
                                ->orWhereHas('demographic', function ($demographicQuery) use ($like) {
                                    $demographicQuery
                                        ->where('first_name', 'like', $like)
                                        ->orWhere('last_name', 'like', $like)
                                        ->orWhere('middle_name', 'like', $like)
                                        ->orWhere('phone_number', 'like', $like);
                                });
                        });
                });
        });
    }

    public function create(Newborn $newborn)
    {
        return view('midwife.newborn-examination.create', compact('newborn'));
    }

    public function store(Request $request, Newborn $newborn)
{
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Examination Details
        |--------------------------------------------------------------------------
        */

        'examination_date_time' => [
            'nullable',
            'date',
        ],

        'hours_after_birth' => [
            'nullable',
            'integer',
            'min:0',
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
        | Anthropometry
        |--------------------------------------------------------------------------
        */

        'weight' => [
            'nullable',
            'numeric',
            'min:300',
            'max:10000',
        ],

        'length' => [
            'nullable',
            'numeric',
            'between:20,100',
        ],

        'head_circumference' => [
            'nullable',
            'numeric',
            'between:10,80',
        ],

        'chest_circumference' => [
            'nullable',
            'numeric',
            'between:10,80',
        ],

        /*
        |--------------------------------------------------------------------------
        | General Examination
        |--------------------------------------------------------------------------
        */

        'general_appearance' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'skin_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'head_and_neck' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'eyes_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'ear_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'mouth_and_throat' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Cardiovascular Examination
        |--------------------------------------------------------------------------
        */

        'heart_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'pulses' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'capillary_refill' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Respiratory Examination
        |--------------------------------------------------------------------------
        */

        'chest_expansion' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'breath_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'nasal_breathing' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Abdominal Examination
        |--------------------------------------------------------------------------
        */

        'abdomen_shape' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'umbilical_cord_check' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'hepatomegaly_splenomegaly' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'bowel_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Genitourinary Examination
        |--------------------------------------------------------------------------
        */

        'genitalia_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'urinary_output' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'stool_output' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Neurological Examination
        |--------------------------------------------------------------------------
        */

        'reflex_assessment' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'muscle_tone' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'developmental_screening' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Musculoskeletal Examination
        |--------------------------------------------------------------------------
        */

        'extremities_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'hip_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'spine_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Special Findings
        |--------------------------------------------------------------------------
        */

        'abnormal_findings' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'congenital_anomalies' => [
            'nullable',
            'string',
            'max:10000',
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
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type' => [
            'nullable',
            'in:breast,bottle,mixed',
        ],

        'feeding_tolerance' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'feeding_challenges' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Overall Assessment
        |--------------------------------------------------------------------------
        */

        'clinical_summary' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'exam_status' => [
            'nullable',
            'in:normal,abnormal,needs_follow_up,referral_needed',
        ],

        'follow_up_plans' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'next_follow_up_date' => [
            'nullable',
            'date',
        ],

    ]);
    $validated['examination_date_time'] = $validated['examination_date_time'] ?? now();
    $validated['exam_status'] = $validated['exam_status'] ?? 'normal';

    /*
    |--------------------------------------------------------------------------
    | Store Examination
    |--------------------------------------------------------------------------
    */

    $newbornExamination = NewbornExamination::create([

        'newborn_id' => $newborn->id,

        'recorded_by' => auth()->id(),

        /*
        |--------------------------------------------------------------------------
        | Examination Details
        |--------------------------------------------------------------------------
        */

        'examination_date_time'
            => $validated['examination_date_time'],

        'hours_after_birth'
            => $validated['hours_after_birth'] ?? null,

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
        | Anthropometry
        |--------------------------------------------------------------------------
        */

        'weight'
            => $validated['weight'] ?? null,

        'length'
            => $validated['length'] ?? null,

        'head_circumference'
            => $validated['head_circumference'] ?? null,

        'chest_circumference'
            => $validated['chest_circumference'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | General Examination
        |--------------------------------------------------------------------------
        */

        'general_appearance'
            => $validated['general_appearance'] ?? null,

        'skin_examination'
            => $validated['skin_examination'] ?? null,

        'head_and_neck'
            => $validated['head_and_neck'] ?? null,

        'eyes_examination'
            => $validated['eyes_examination'] ?? null,

        'ear_examination'
            => $validated['ear_examination'] ?? null,

        'mouth_and_throat'
            => $validated['mouth_and_throat'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Cardiovascular Examination
        |--------------------------------------------------------------------------
        */

        'heart_sounds'
            => $validated['heart_sounds'] ?? null,

        'pulses'
            => $validated['pulses'] ?? null,

        'capillary_refill'
            => $validated['capillary_refill'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Respiratory Examination
        |--------------------------------------------------------------------------
        */

        'chest_expansion'
            => $validated['chest_expansion'] ?? null,

        'breath_sounds'
            => $validated['breath_sounds'] ?? null,

        'nasal_breathing'
            => $validated['nasal_breathing'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Abdominal Examination
        |--------------------------------------------------------------------------
        */

        'abdomen_shape'
            => $validated['abdomen_shape'] ?? null,

        'umbilical_cord_check'
            => $validated['umbilical_cord_check'] ?? null,

        'hepatomegaly_splenomegaly'
            => $validated['hepatomegaly_splenomegaly'] ?? null,

        'bowel_sounds'
            => $validated['bowel_sounds'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Genitourinary Examination
        |--------------------------------------------------------------------------
        */

        'genitalia_examination'
            => $validated['genitalia_examination'] ?? null,

        'urinary_output'
            => $validated['urinary_output'] ?? null,

        'stool_output'
            => $validated['stool_output'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Neurological Examination
        |--------------------------------------------------------------------------
        */

        'reflex_assessment'
            => $validated['reflex_assessment'] ?? null,

        'muscle_tone'
            => $validated['muscle_tone'] ?? null,

        'developmental_screening'
            => $validated['developmental_screening'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Musculoskeletal Examination
        |--------------------------------------------------------------------------
        */

        'extremities_examination'
            => $validated['extremities_examination'] ?? null,

        'hip_examination'
            => $validated['hip_examination'] ?? null,

        'spine_examination'
            => $validated['spine_examination'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Special Findings
        |--------------------------------------------------------------------------
        */

        'abnormal_findings'
            => $validated['abnormal_findings'] ?? null,

        'congenital_anomalies'
            => $validated['congenital_anomalies'] ?? null,

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
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type'
            => $validated['feeding_type'] ?? null,

        'feeding_tolerance'
            => $validated['feeding_tolerance'] ?? null,

        'feeding_challenges'
            => $validated['feeding_challenges'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Overall Assessment
        |--------------------------------------------------------------------------
        */

        'clinical_summary'
            => $validated['clinical_summary'] ?? null,

        'exam_status'
            => $validated['exam_status'],

        'follow_up_plans'
            => $validated['follow_up_plans'] ?? null,

        'next_follow_up_date'
            => $validated['next_follow_up_date'] ?? null,

    ]);
    // log activity
    $newborn->patient->currentVisit()->visitActivities()->create([
        'activity' => "Newborn examination recorded with status: {$newbornExamination->exam_status}",
        'recorded_by' => auth()->id(),
    ]);
    
     /*
     |--------------------------------------------------------------------------
     | Redirect
     |--------------------------------------------------------------------------
     */ 
   
        
    return redirect()
        ->route('midwife.newborn-examination.show', $newbornExamination)
        ->with('success', 'Newborn examination recorded successfully.');
}

    public function show(NewbornExamination $newbornExamination)
    {
        
        return view('midwife.newborn-examination.show', compact('newbornExamination'));
    }

    public function record(Newborn $newborn)
    {
        $newborn->load('delivery.patient.demographic', 'examinations.recordedBy');

        return view('midwife.newborn-examination.record', compact('newborn'));
    }

    public function edit(NewbornExamination $newbornExamination)
    {
        return view('midwife.newborn-examination.edit', compact('newbornExamination'));
    }

    public function update(Request $request, NewbornExamination $newbornExamination)
{
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Examination Details
        |--------------------------------------------------------------------------
        */

        'examination_date_time' => [
            'nullable',
            'date',
        ],

        'hours_after_birth' => [
            'nullable',
            'integer',
            'min:0',
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
        | Anthropometry
        |--------------------------------------------------------------------------
        */

        'weight' => [
            'nullable',
            'numeric',
            'min:300',
            'max:10000',
        ],

        'length' => [
            'nullable',
            'numeric',
            'between:20,100',
        ],

        'head_circumference' => [
            'nullable',
            'numeric',
            'between:10,80',
        ],

        'chest_circumference' => [
            'nullable',
            'numeric',
            'between:10,80',
        ],

        /*
        |--------------------------------------------------------------------------
        | General Examination
        |--------------------------------------------------------------------------
        */

        'general_appearance' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'skin_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'head_and_neck' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'eyes_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'ear_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'mouth_and_throat' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Cardiovascular Examination
        |--------------------------------------------------------------------------
        */

        'heart_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'pulses' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'capillary_refill' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Respiratory Examination
        |--------------------------------------------------------------------------
        */

        'chest_expansion' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'breath_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'nasal_breathing' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Abdominal Examination
        |--------------------------------------------------------------------------
        */

        'abdomen_shape' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'umbilical_cord_check' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'hepatomegaly_splenomegaly' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'bowel_sounds' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Genitourinary Examination
        |--------------------------------------------------------------------------
        */

        'genitalia_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'urinary_output' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'stool_output' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Neurological Examination
        |--------------------------------------------------------------------------
        */

        'reflex_assessment' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'muscle_tone' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'developmental_screening' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Musculoskeletal Examination
        |--------------------------------------------------------------------------
        */

        'extremities_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'hip_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'spine_examination' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Special Findings
        |--------------------------------------------------------------------------
        */

        'abnormal_findings' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'congenital_anomalies' => [
            'nullable',
            'string',
            'max:10000',
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
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type' => [
            'nullable',
            'in:breast,bottle,mixed',
        ],

        'feeding_tolerance' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'feeding_challenges' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Overall Assessment
        |--------------------------------------------------------------------------
        */

        'clinical_summary' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'exam_status' => [
            'nullable',
            'in:normal,abnormal,needs_follow_up,referral_needed',
        ],

        'follow_up_plans' => [
            'nullable',
            'string',
            'max:10000',
        ],

        'next_follow_up_date' => [
            'nullable',
            'date',
        ],

    ]);
    $validated['examination_date_time'] = $validated['examination_date_time'] ?? $newbornExamination->examination_date_time ?? now();
    $validated['exam_status'] = $validated['exam_status'] ?? $newbornExamination->exam_status ?? 'normal';

    /*
    |--------------------------------------------------------------------------
    | Update Examination
    |--------------------------------------------------------------------------
    */

    $newbornExamination->update([

        /*
        |--------------------------------------------------------------------------
        | Examination Details
        |--------------------------------------------------------------------------
        */

        'examination_date_time'
            => $validated['examination_date_time'],

        'hours_after_birth'
            => $validated['hours_after_birth'] ?? null,

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
        | Anthropometry
        |--------------------------------------------------------------------------
        */

        'weight'
            => $validated['weight'] ?? null,

        'length'
            => $validated['length'] ?? null,

        'head_circumference'
            => $validated['head_circumference'] ?? null,

        'chest_circumference'
            => $validated['chest_circumference'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | General Examination
        |--------------------------------------------------------------------------
        */

        'general_appearance'
            => $validated['general_appearance'] ?? null,

        'skin_examination'
            => $validated['skin_examination'] ?? null,

        'head_and_neck'
            => $validated['head_and_neck'] ?? null,

        'eyes_examination'
            => $validated['eyes_examination'] ?? null,

        'ear_examination'
            => $validated['ear_examination'] ?? null,

        'mouth_and_throat'
            => $validated['mouth_and_throat'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Cardiovascular Examination
        |--------------------------------------------------------------------------
        */

        'heart_sounds'
            => $validated['heart_sounds'] ?? null,

        'pulses'
            => $validated['pulses'] ?? null,

        'capillary_refill'
            => $validated['capillary_refill'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Respiratory Examination
        |--------------------------------------------------------------------------
        */

        'chest_expansion'
            => $validated['chest_expansion'] ?? null,

        'breath_sounds'
            => $validated['breath_sounds'] ?? null,

        'nasal_breathing'
            => $validated['nasal_breathing'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Abdominal Examination
        |--------------------------------------------------------------------------
        */

        'abdomen_shape'
            => $validated['abdomen_shape'] ?? null,

        'umbilical_cord_check'
            => $validated['umbilical_cord_check'] ?? null,

        'hepatomegaly_splenomegaly'
            => $validated['hepatomegaly_splenomegaly'] ?? null,

        'bowel_sounds'
            => $validated['bowel_sounds'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Genitourinary Examination
        |--------------------------------------------------------------------------
        */

        'genitalia_examination'
            => $validated['genitalia_examination'] ?? null,

        'urinary_output'
            => $validated['urinary_output'] ?? null,

        'stool_output'
            => $validated['stool_output'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Neurological Examination
        |--------------------------------------------------------------------------
        */

        'reflex_assessment'
            => $validated['reflex_assessment'] ?? null,

        'muscle_tone'
            => $validated['muscle_tone'] ?? null,

        'developmental_screening'
            => $validated['developmental_screening'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Musculoskeletal Examination
        |--------------------------------------------------------------------------
        */

        'extremities_examination'
            => $validated['extremities_examination'] ?? null,

        'hip_examination'
            => $validated['hip_examination'] ?? null,

        'spine_examination'
            => $validated['spine_examination'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Special Findings
        |--------------------------------------------------------------------------
        */

        'abnormal_findings'
            => $validated['abnormal_findings'] ?? null,

        'congenital_anomalies'
            => $validated['congenital_anomalies'] ?? null,

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
        | Feeding Assessment
        |--------------------------------------------------------------------------
        */

        'feeding_type'
            => $validated['feeding_type'] ?? null,

        'feeding_tolerance'
            => $validated['feeding_tolerance'] ?? null,

        'feeding_challenges'
            => $validated['feeding_challenges'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Overall Assessment
        |--------------------------------------------------------------------------
        */

        'clinical_summary'
            => $validated['clinical_summary'] ?? null,

        'exam_status'
            => $validated['exam_status'],

        'follow_up_plans'
            => $validated['follow_up_plans'] ?? null,

        'next_follow_up_date'
            => $validated['next_follow_up_date'] ?? null,

    ]);
    // log activity
    $newborn = $newbornExamination->newborn;
    $newborn->patient->currentVisit()->visitActivities()->create([
        'activity' => "Newborn examination updated with status: {$newbornExamination->exam_status}",
        'recorded_by' => auth()->id(),
    ]);
    
     /*
     |--------------------------------------------------------------------------
     | Redirect
     |--------------------------------------------------------------------------
     */

    return redirect()
        ->route('midwife.newborn-examination.show', $newbornExamination)
        ->with('success', 'Newborn examination updated successfully.');
}

    public function destroy(NewbornExamination $newbornExamination)
    {
        $patient = $newbornExamination->newborn->patient;

        $newbornExamination->delete();

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Newborn examination record deleted successfully.');
    }
}
