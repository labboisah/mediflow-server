<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\PostnatalExamination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostnatalExaminationController extends Controller
{
    

    public function index()
    {
        $search = trim((string) request('q'));

        $postnatalExaminations = PostnatalExamination::query()
            ->with(['patient.demographic', 'delivery', 'recordedBy'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('examination_date_time')
            ->get();

        return view('midwife.postnatal-examination.index', compact('postnatalExaminations', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('recovery_status', 'like', $like)
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
        return view('midwife.postnatal-examination.create', compact('delivery'));
    }

    public function record(Delivery $delivery)
    {

        return view('midwife.postnatal-examination.record', compact('delivery'));
    }

    public function store(Request $request, Delivery $delivery)
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

            'hours_post_delivery' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'examination_time' => [
                'nullable',
                'in:immediate_0-2h,6-12h,24h,48h,day4_6,week1,week2,week6',
            ],

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
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
                'between:30,200',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'between:30,45',
            ],

            'respiration_rate' => [
                'nullable',
                'integer',
                'between:5,80',
            ],

            /*
            |--------------------------------------------------------------------------
            | General Assessment
            |--------------------------------------------------------------------------
            */

            'general_appearance' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'consciousness_level' => [
                'nullable',
                'in:alert,drowsy,unconscious',
            ],

            'skin_colour' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Uterine Assessment
            |--------------------------------------------------------------------------
            */

            'uterine_size' => [
                'nullable',
                'string',
                'max:255',
            ],

            'uterine_consistency' => [
                'nullable',
                'in:firm,soft,boggy',
            ],

            'uterine_tenderness' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fundal_height' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lochia Assessment
            |--------------------------------------------------------------------------
            */

            'lochia_type' => [
                'nullable',
                'in:rubra,serosa,alba',
            ],

            'lochia_amount' => [
                'nullable',
                'in:minimal,moderate,heavy',
            ],

            'lochia_odour' => [
                'nullable',
                'string',
                'max:255',
            ],

            'clot_presence' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Perineal Assessment
            |--------------------------------------------------------------------------
            */

            'perineal_assessment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'perineal_wound_status' => [
                'nullable',
                'in:intact,sutured,healing,healed',
            ],

            'perineal_pain' => [
                'nullable',
                'string',
                'max:255',
            ],

            'vaginal_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Breastfeeding Assessment
            |--------------------------------------------------------------------------
            */

            'breast_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'nipple_condition' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breast_engorgement' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breast_milk_expression' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breastfeeding_successful' => [
                'nullable',
                'boolean',
            ],

            'breastfeeding_problems' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Additional Assessments
            |--------------------------------------------------------------------------
            */

            'abdominal_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'wound_assessment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'drain_status' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'lower_limbs_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'oedema_assessment' => [
                'nullable',
                'string',
                'max:255',
            ],

            'calf_tenderness' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'signs_of_dvt' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Mental Health
            |--------------------------------------------------------------------------
            */

            'maternal_mood' => [
                'nullable',
                'string',
                'max:255',
            ],

            'emotional_state' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'signs_of_depression' => [
                'nullable',
                'boolean',
            ],

            'bonding_with_baby' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Summary & Management
            |--------------------------------------------------------------------------
            */

            'clinical_summary' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'recovery_status' => [
                'nullable',
                'in:normal,complicated,needs_referral',
            ],

            'management_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'medications_prescribed' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'follow_up_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'next_follow_up_date' => [
                'nullable',
                'date',
            ],

            'contraception_discussed' => [
                'nullable',
                'boolean',
            ],

            'contraception_method_chosen' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);

        $validated['examination_date_time'] = $validated['examination_date_time'] ?? now();
        $validated['recovery_status'] = $validated['recovery_status'] ?? 'normal';

        /*
        |--------------------------------------------------------------------------
        | Store Postnatal Examination
        |--------------------------------------------------------------------------
        */

        $postnatalExamination = PostnatalExamination::create([

            'delivery_id' => $delivery->id,

            'patient_id' => $delivery->patient_id,

            'patient_visit_id' => $delivery->patient_visit_id ?? $delivery->patient?->currentVisit()?->id,

            'recorded_by' => auth()->id(),

            /*
            |--------------------------------------------------------------------------
            | Examination Details
            |--------------------------------------------------------------------------
            */

            'examination_date_time'
                => $validated['examination_date_time'],

            'hours_post_delivery'
                => $validated['hours_post_delivery'] ?? null,

            'examination_time'
                => $validated['examination_time'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
            |--------------------------------------------------------------------------
            */

            'blood_pressure'
                => $validated['blood_pressure'] ?? null,

            'pulse_rate'
                => $validated['pulse_rate'] ?? null,

            'temperature'
                => $validated['temperature'] ?? null,

            'respiration_rate'
                => $validated['respiration_rate'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | General Assessment
            |--------------------------------------------------------------------------
            */

            'general_appearance'
                => $validated['general_appearance'] ?? null,

            'consciousness_level'
                => $validated['consciousness_level'] ?? null,

            'skin_colour'
                => $validated['skin_colour'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Uterine Assessment
            |--------------------------------------------------------------------------
            */

            'uterine_size'
                => $validated['uterine_size'] ?? null,

            'uterine_consistency'
                => $validated['uterine_consistency'] ?? null,

            'uterine_tenderness'
                => $validated['uterine_tenderness'] ?? null,

            'fundal_height'
                => $validated['fundal_height'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Lochia Assessment
            |--------------------------------------------------------------------------
            */

            'lochia_type'
                => $validated['lochia_type'] ?? null,

            'lochia_amount'
                => $validated['lochia_amount'] ?? null,

            'lochia_odour'
                => $validated['lochia_odour'] ?? null,

            'clot_presence'
                => $request->boolean('clot_presence'),

            /*
            |--------------------------------------------------------------------------
            | Perineal Assessment
            |--------------------------------------------------------------------------
            */

            'perineal_assessment'
                => $validated['perineal_assessment'] ?? null,

            'perineal_wound_status'
                => $validated['perineal_wound_status'] ?? null,

            'perineal_pain'
                => $validated['perineal_pain'] ?? null,

            'vaginal_examination'
                => $validated['vaginal_examination'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Breastfeeding Assessment
            |--------------------------------------------------------------------------
            */

            'breast_examination'
                => $validated['breast_examination'] ?? null,

            'nipple_condition'
                => $validated['nipple_condition'] ?? null,

            'breast_engorgement'
                => $validated['breast_engorgement'] ?? null,

            'breast_milk_expression'
                => $validated['breast_milk_expression'] ?? null,

            'breastfeeding_successful'
                => $request->boolean('breastfeeding_successful'),

            'breastfeeding_problems'
                => $validated['breastfeeding_problems'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Additional Assessments
            |--------------------------------------------------------------------------
            */

            'abdominal_examination'
                => $validated['abdominal_examination'] ?? null,

            'wound_assessment'
                => $validated['wound_assessment'] ?? null,

            'drain_status'
                => $validated['drain_status'] ?? null,

            'lower_limbs_examination'
                => $validated['lower_limbs_examination'] ?? null,

            'oedema_assessment'
                => $validated['oedema_assessment'] ?? null,

            'calf_tenderness'
                => $validated['calf_tenderness'] ?? null,

            'signs_of_dvt'
                => $validated['signs_of_dvt'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Mental Health
            |--------------------------------------------------------------------------
            */

            'maternal_mood'
                => $validated['maternal_mood'] ?? null,

            'emotional_state'
                => $validated['emotional_state'] ?? null,

            'signs_of_depression'
                => $request->boolean('signs_of_depression'),

            'bonding_with_baby'
                => $validated['bonding_with_baby'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Summary & Management
            |--------------------------------------------------------------------------
            */

            'clinical_summary'
                => $validated['clinical_summary'] ?? null,

            'recovery_status'
                => $validated['recovery_status'],

            'management_plan'
                => $validated['management_plan'] ?? null,

            'medications_prescribed'
                => $validated['medications_prescribed'] ?? null,

            'follow_up_plan'
                => $validated['follow_up_plan'] ?? null,

            'next_follow_up_date'
                => $validated['next_follow_up_date'] ?? null,

            'contraception_discussed'
                => $request->boolean('contraception_discussed'),

            'contraception_method_chosen'
                => $validated['contraception_method_chosen'] ?? null,

        ]);
        // Log activity
        $postnatalExamination->delivery->patient->currentVisit()->visitActivities()->create([
            'activity' => "Postnatal examination recorded for delivery ID: {$delivery->id}",
            'recorded_by' => auth()->id(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('midwife.postnatal-examination.show', $postnatalExamination)
            ->with('success', 'Postnatal examination recorded successfully.');
    }

    public function show(PostnatalExamination $postnatalExamination)
    {
        $postnatalExamination->load('delivery.patient.demographic', 'recordedBy');

        return view('midwife.postnatal-examination.show', compact('postnatalExamination'));
    }

    public function edit(PostnatalExamination $postnatalExamination)
    {
        return view('midwife.postnatal-examination.edit', compact('postnatalExamination'));
    }

    public function update(Request $request, PostnatalExamination $postnatalExamination)
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

            'hours_post_delivery' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'examination_time' => [
                'nullable',
                'in:immediate_0-2h,6-12h,24h,48h,day4_6,week1,week2,week6',
            ],

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
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
                'between:30,200',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'between:30,45',
            ],

            'respiration_rate' => [
                'nullable',
                'integer',
                'between:5,80',
            ],

            /*
            |--------------------------------------------------------------------------
            | General Assessment
            |--------------------------------------------------------------------------
            */

            'general_appearance' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'consciousness_level' => [
                'nullable',
                'in:alert,drowsy,unconscious',
            ],

            'skin_colour' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Uterine Assessment
            |--------------------------------------------------------------------------
            */

            'uterine_size' => [
                'nullable',
                'string',
                'max:255',
            ],

            'uterine_consistency' => [
                'nullable',
                'in:firm,soft,boggy',
            ],

            'uterine_tenderness' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fundal_height' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lochia Assessment
            |--------------------------------------------------------------------------
            */

            'lochia_type' => [
                'nullable',
                'in:rubra,serosa,alba',
            ],

            'lochia_amount' => [
                'nullable',
                'in:minimal,moderate,heavy',
            ],

            'lochia_odour' => [
                'nullable',
                'string',
                'max:255',
            ],

            'clot_presence' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Perineal Assessment
            |--------------------------------------------------------------------------
            */

            'perineal_assessment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'perineal_wound_status' => [
                'nullable',
                'in:intact,sutured,healing,healed',
            ],

            'perineal_pain' => [
                'nullable',
                'string',
                'max:255',
            ],

            'vaginal_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Breastfeeding Assessment
            |--------------------------------------------------------------------------
            */

            'breast_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'nipple_condition' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breast_engorgement' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breast_milk_expression' => [
                'nullable',
                'string',
                'max:255',
            ],

            'breastfeeding_successful' => [
                'nullable',
                'boolean',
            ],

            'breastfeeding_problems' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Additional Assessments
            |--------------------------------------------------------------------------
            */

            'abdominal_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'wound_assessment' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'drain_status' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'lower_limbs_examination' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'oedema_assessment' => [
                'nullable',
                'string',
                'max:255',
            ],

            'calf_tenderness' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'signs_of_dvt' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Mental Health
            |--------------------------------------------------------------------------
            */

            'maternal_mood' => [
                'nullable',
                'string',
                'max:255',
            ],

            'emotional_state' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'signs_of_depression' => [
                'nullable',
                'boolean',
            ],

            'bonding_with_baby' => [
                'nullable',
                'string',
                'max:5000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Summary & Management
            |--------------------------------------------------------------------------
            */

            'clinical_summary' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'recovery_status' => [
                'nullable',
                'in:normal,complicated,needs_referral',
            ],

            'management_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'medications_prescribed' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'follow_up_plan' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'next_follow_up_date' => [
                'nullable',
                'date',
            ],

            'contraception_discussed' => [
                'nullable',
                'boolean',
            ],

            'contraception_method_chosen' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);
        $validated['examination_date_time'] = $validated['examination_date_time'] ?? $postnatalExamination->examination_date_time ?? now();
        $validated['recovery_status'] = $validated['recovery_status'] ?? $postnatalExamination->recovery_status ?? 'normal';

        /*
        |--------------------------------------------------------------------------
        | Update Postnatal Examination
        |--------------------------------------------------------------------------
        */

        $postnatalExamination->update([

            /*
            |--------------------------------------------------------------------------
            | Examination Details
            |--------------------------------------------------------------------------
            */

            'examination_date_time'
                => $validated['examination_date_time'],

            'hours_post_delivery'
                => $validated['hours_post_delivery'] ?? null,

            'examination_time'
                => $validated['examination_time'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Vital Signs
            |--------------------------------------------------------------------------
            */

            'blood_pressure'
                => $validated['blood_pressure'] ?? null,

            'pulse_rate'
                => $validated['pulse_rate'] ?? null,

            'temperature'
                => $validated['temperature'] ?? null,

            'respiration_rate'
                => $validated['respiration_rate'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | General Assessment
            |--------------------------------------------------------------------------
            */

            'general_appearance'
                => $validated['general_appearance'] ?? null,

            'consciousness_level'
                => $validated['consciousness_level'] ?? null,

            'skin_colour'
                => $validated['skin_colour'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Uterine Assessment
            |--------------------------------------------------------------------------
            */

            'uterine_size'
                => $validated['uterine_size'] ?? null,

            'uterine_consistency'
                => $validated['uterine_consistency'] ?? null,

            'uterine_tenderness'
                => $validated['uterine_tenderness'] ?? null,

            'fundal_height'
                => $validated['fundal_height'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Lochia Assessment
            |--------------------------------------------------------------------------
            */

            'lochia_type'
                => $validated['lochia_type'] ?? null,

            'lochia_amount'
                => $validated['lochia_amount'] ?? null,

            'lochia_odour'
                => $validated['lochia_odour'] ?? null,

            'clot_presence'
                => $request->boolean('clot_presence'),

            /*
            |--------------------------------------------------------------------------
            | Perineal Assessment
            |--------------------------------------------------------------------------
            */

            'perineal_assessment'
                => $validated['perineal_assessment'] ?? null,

            'perineal_wound_status'
                => $validated['perineal_wound_status'] ?? null,

            'perineal_pain'
                => $validated['perineal_pain'] ?? null,

            'vaginal_examination'
                => $validated['vaginal_examination'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Breastfeeding Assessment
            |--------------------------------------------------------------------------
            */

            'breast_examination'
                => $validated['breast_examination'] ?? null,

            'nipple_condition'
                => $validated['nipple_condition'] ?? null,

            'breast_engorgement'
                => $validated['breast_engorgement'] ?? null,

            'breast_milk_expression'
                => $validated['breast_milk_expression'] ?? null,

            'breastfeeding_successful'
                => $request->boolean('breastfeeding_successful'),

            'breastfeeding_problems'
                => $validated['breastfeeding_problems'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Additional Assessments
            |--------------------------------------------------------------------------
            */

            'abdominal_examination'
                => $validated['abdominal_examination'] ?? null,

            'wound_assessment'
                => $validated['wound_assessment'] ?? null,

            'drain_status'
                => $validated['drain_status'] ?? null,

            'lower_limbs_examination'
                => $validated['lower_limbs_examination'] ?? null,

            'oedema_assessment'
                => $validated['oedema_assessment'] ?? null,

            'calf_tenderness'
                => $validated['calf_tenderness'] ?? null,

            'signs_of_dvt'
                => $validated['signs_of_dvt'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Mental Health
            |--------------------------------------------------------------------------
            */

            'maternal_mood'
                => $validated['maternal_mood'] ?? null,

            'emotional_state'
                => $validated['emotional_state'] ?? null,

            'signs_of_depression'
                => $request->boolean('signs_of_depression'),

            'bonding_with_baby'
                => $validated['bonding_with_baby'] ?? null,

            /*
            |--------------------------------------------------------------------------
            | Summary & Management
            |--------------------------------------------------------------------------
            */

            'clinical_summary'
                => $validated['clinical_summary'] ?? null,

            'recovery_status'
                => $validated['recovery_status'],

            'management_plan'
                => $validated['management_plan'] ?? null,

            'medications_prescribed'
                => $validated['medications_prescribed'] ?? null,

            'follow_up_plan'
                => $validated['follow_up_plan'] ?? null,

            'next_follow_up_date'
                => $validated['next_follow_up_date'] ?? null,

            'contraception_discussed'
                => $request->boolean('contraception_discussed'),

            'contraception_method_chosen'
                => $validated['contraception_method_chosen'] ?? null,

        ]);

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('midwife.postnatal-examination.show', $postnatalExamination)
            ->with('success', 'Postnatal examination updated successfully.');
    }

    public function destroy(PostnatalExamination $postnatalExamination)
    {
        $patient = $postnatalExamination->patient;

        $postnatalExamination->delete();

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Postnatal examination record deleted successfully.');
    }
}
