<?php

namespace App\Livewire\Midwife;

use App\Models\AntenatalCare;
use App\Models\ChildFollowUp;
use App\Models\Delivery;
use App\Models\Labour;
use App\Models\Newborn;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PostnatalExamination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.modern')]
class MaternityVisitWorkspace extends Component
{
    public string $search = '';
    public ?int $patientId = null;
    public ?int $visitId = null;
    public string $activity = 'antenatal';
    public ?string $fixedActivity = null;
    public string $pageTitle = 'Maternity Visit Workflow';
    public string $pageDescription = 'Record ANC, labour, delivery, newborn, postnatal, or child follow-up directly on this patient visit.';
    public bool $compact = false;
    public array $form = [];

    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->compact = $compact;
        $this->resolveRoutePatient($patient);

        if ($patient && $patient->exists) {
            $this->patientId = $patient->id;
            $this->visitId = $this->activeVisit($patient)?->id;
        }

        $this->resetForm();
    }

    public function render()
    {
        $patient = $this->patient();
        $visit = $this->visit();

        return view('components.midwife.maternity-visit-workspace', [
            'patients' => $this->patients(),
            'patient' => $patient,
            'visit' => $visit,
            'activityCounts' => $visit ? $this->activityCounts($visit) : [],
            'labours' => $patient ? $patient->labours()->latest('created_at')->get() : collect(),
            'deliveries' => $patient ? $patient->deliveries()->latest('created_at')->get() : collect(),
            'newborns' => $patient ? $patient->newborns()->latest('created_at')->get() : collect(),
        ]);
    }

    public function selectPatient(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->visitId = $this->activeVisit($this->patient())?->id;
        $this->resetForm();
        $this->dispatch('toast', message: 'Patient loaded for maternity activities.', type: 'success');
    }

    public function setActivity(string $activity): void
    {
        if ($this->fixedActivity) {
            return;
        }

        $this->activity = $activity;
        $this->resetForm();
    }

    public function save(): void
    {
        if (! $this->patient()) {
            $this->addError('patient', 'Select a patient before recording an activity.');
            return;
        }

        if (! $this->patientIsFemale($this->patient())) {
            $this->addError('patient', 'Maternity activity can only be recorded for female patients.');
            $this->dispatch('toast', message: 'Maternity activity can only be recorded for female patients.', type: 'danger');
            return;
        }

        $patient = $this->patient();

        try {
            DB::transaction(function () use ($patient) {
                $visit = $this->visit() ?? $this->activeVisit($patient) ?? $this->createMaternityVisit($patient);
                $this->visitId = $visit->id;

                match ($this->activity) {
                    'antenatal' => $this->saveAntenatal($visit),
                    'labour' => $this->saveLabour($visit),
                    'delivery' => $this->saveDelivery($visit),
                    'newborn' => $this->saveNewborn($visit),
                    'postnatal' => $this->savePostnatal($visit),
                    'child_follow_up' => $this->saveChildFollowUp($visit),
                    default => null,
                };
            });
        } catch (ValidationException $exception) {
            $this->dispatch('toast', message: 'Please check the highlighted fields.', type: 'warning');
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('save', 'The maternity activity could not be saved. Please check the form and try again.');
            $this->dispatch('toast', message: 'Maternity activity was not saved.', type: 'danger');
            return;
        }

        $this->resetForm();
        $this->dispatch('toast', message: 'Maternity activity saved and linked to this patient visit.', type: 'success');
    }

    private function saveAntenatal(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.last_menstrual_period' => ['nullable', 'date'],
            'form.expected_delivery_date' => ['nullable', 'date'],
            'form.gestational_weeks' => ['nullable', 'integer', 'min:1', 'max:45'],
            'form.number_of_fetuses' => ['nullable', 'integer', 'min:1', 'max:8'],
            'form.pregnancy_type' => ['nullable', 'string', 'max:50'],
            'form.blood_pressure' => ['nullable', 'string', 'max:20'],
            'form.weight' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'form.height' => ['nullable', 'numeric', 'min:50', 'max:250'],
            'form.abdominal_examination' => ['nullable', 'string'],
            'form.fundal_height' => ['nullable', 'string', 'max:20'],
            'form.fetal_heart_rate' => ['nullable', 'string', 'max:20'],
            'form.fetal_movement' => ['nullable', 'string'],
            'form.vaginal_examination' => ['nullable', 'string'],
            'form.urine_analysis' => ['nullable', 'string'],
            'form.blood_tests' => ['nullable', 'string'],
            'form.ultrasound_findings' => ['nullable', 'string'],
            'form.risk_factors' => ['nullable', 'string'],
            'form.complications' => ['nullable', 'string'],
            'form.management_plan' => ['nullable', 'string'],
            'form.counseling_topics' => ['nullable', 'string'],
            'form.took_supplements' => ['boolean'],
            'form.status' => ['nullable', 'in:normal,complicated,high_risk'],
            'form.clinical_notes' => ['nullable', 'string', 'max:5000'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);
        $validated['status'] = $validated['status'] ?: 'normal';

        AntenatalCare::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'recorded_by' => auth()->id(),
            'number_of_fetuses' => $validated['number_of_fetuses'] ?: 1,
        ]);

        $this->logVisitActivity($visit, 'Antenatal care recorded');
    }

    private function saveLabour(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.labour_onset_time' => ['nullable', 'date'],
            'form.mode_of_onset' => ['nullable', 'in:spontaneous,induced'],
            'form.reason_for_induction' => ['nullable', 'string', 'max:1000'],
            'form.gestational_weeks' => ['nullable', 'integer', 'min:20', 'max:45'],
            'form.labour_type' => ['nullable', 'string', 'max:255'],
            'form.previous_obstetric_history' => ['nullable', 'string', 'max:3000'],
            'form.cervical_state' => ['nullable', 'string', 'max:255'],
            'form.show' => ['nullable', 'in:present,absent'],
            'form.rupture_of_membranes' => ['nullable', 'in:intact,spontaneous rupture,artificial rupture'],
            'form.liquor' => ['nullable', 'string', 'max:1000'],
            'form.blood_pressure' => ['nullable', 'string', 'max:20'],
            'form.pulse_rate' => ['nullable', 'string', 'max:20'],
            'form.temperature' => ['nullable', 'string', 'max:20'],
            'form.respiration_rate' => ['nullable', 'string', 'max:20'],
            'form.stage' => ['nullable', 'in:not_started,first_stage,second_stage,third_stage,completed'],
            'form.first_stage_started_at' => ['nullable', 'date'],
            'form.second_stage_started_at' => ['nullable', 'date'],
            'form.third_stage_started_at' => ['nullable', 'date'],
            'form.fetal_heart_rate' => ['nullable', 'string', 'max:20'],
            'form.fetal_monitoring_notes' => ['nullable', 'string', 'max:3000'],
            'form.complications' => ['nullable', 'string', 'max:3000'],
            'form.status' => ['nullable', 'in:ongoing,completed,complicated'],
            'form.clinical_notes' => ['nullable', 'string', 'max:5000'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);

        $validated['labour_onset_time'] = $validated['labour_onset_time'] ?: now();
        $validated['stage'] = $validated['stage'] ?: 'not_started';
        $validated['status'] = $validated['status'] ?: 'ongoing';

        $labour = Labour::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'admission_id' => null,
            'recorded_by' => auth()->id(),
        ]);

        $this->logVisitActivity($visit, "Labour recorded with status: {$labour->status}");
    }

    private function saveDelivery(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.labour_id' => ['nullable', 'exists:labours,id'],
            'form.delivery_date_time' => ['nullable', 'date'],
            'form.delivery_type' => ['nullable', 'in:vaginal,assisted_vaginal,caesarean'],
            'form.reason_for_delivery_type' => ['nullable', 'string', 'max:3000'],
            'form.assisted_with' => ['nullable', 'in:vacuum,forceps'],
            'form.indication_for_assistance' => ['nullable', 'string', 'max:3000'],
            'form.caesarean_type' => ['nullable', 'in:elective,emergency'],
            'form.indication_for_caesarean' => ['nullable', 'string', 'max:3000'],
            'form.perineal_trauma' => ['nullable', 'in:intact,1st degree,2nd degree,3rd degree,4th degree'],
            'form.episiotomy' => ['nullable', 'string', 'max:3000'],
            'form.perineal_repair' => ['nullable', 'string', 'max:3000'],
            'form.placenta_delivery_method' => ['nullable', 'in:spontaneous,manual removal'],
            'form.placenta_delivered_at' => ['nullable', 'date'],
            'form.placental_examination' => ['nullable', 'string', 'max:3000'],
            'form.estimated_blood_loss' => ['nullable', 'string', 'max:255'],
            'form.blood_loss_assessment' => ['nullable', 'string', 'max:3000'],
            'form.uterine_tone' => ['nullable', 'string', 'max:255'],
            'form.per_vaginal_bleeding' => ['nullable', 'string', 'max:255'],
            'form.blood_pressure' => ['nullable', 'string', 'max:50'],
            'form.pulse_rate' => ['nullable', 'string', 'max:50'],
            'form.general_condition' => ['nullable', 'string', 'max:255'],
            'form.complications' => ['nullable', 'string', 'max:5000'],
            'form.management_of_complications' => ['nullable', 'string', 'max:5000'],
            'form.number_of_babies' => ['nullable', 'integer', 'min:1', 'max:10'],
            'form.delivery_status' => ['nullable', 'in:successful,complicated,maternal_death,fetal_death'],
            'form.delivery_summary' => ['nullable', 'string', 'max:5000'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);

        $validated['labour_id'] = $validated['labour_id'] ?: null;
        $validated['delivery_date_time'] = $validated['delivery_date_time'] ?: now();
        $validated['delivery_type'] = $validated['delivery_type'] ?: 'vaginal';
        $validated['number_of_babies'] = $validated['number_of_babies'] ?: 1;
        $validated['delivery_status'] = $validated['delivery_status'] ?: 'successful';
        $validated['placenta_delivered_at'] = $validated['placenta_delivered_at'] ?: $validated['delivery_date_time'];

        $delivery = Delivery::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'delivered_by' => auth()->id(),
            'assisted_by' => auth()->id(),
        ]);

        if ($delivery->labour) {
            $delivery->labour->update(['status' => 'completed', 'stage' => 'completed']);
        }

        $this->logVisitActivity($visit, "Delivery recorded with status: {$delivery->delivery_status}");
    }

    private function saveNewborn(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.delivery_id' => ['nullable', 'exists:deliveries,id'],
            'form.sex' => ['nullable', 'in:male,female'],
            'form.birth_order' => ['nullable', 'integer', 'min:1', 'max:10'],
            'form.birth_date_time' => ['nullable', 'date'],
            'form.birth_weight' => ['nullable', 'string', 'max:50'],
            'form.birth_length' => ['nullable', 'string', 'max:50'],
            'form.head_circumference' => ['nullable', 'string', 'max:50'],
            'form.presentation' => ['nullable', 'in:cephalic,breech,transverse,face'],
            'form.delivery_notes' => ['nullable', 'string', 'max:5000'],
            'form.apgar_score_1_minute' => ['nullable', 'integer', 'min:0', 'max:10'],
            'form.apgar_score_5_minutes' => ['nullable', 'integer', 'min:0', 'max:10'],
            'form.apgar_score_10_minutes' => ['nullable', 'integer', 'min:0', 'max:10'],
            'form.apgar_appearance_1min' => ['nullable', 'integer', 'min:0', 'max:2'],
            'form.apgar_pulse_1min' => ['nullable', 'integer', 'min:0', 'max:2'],
            'form.apgar_grimace_1min' => ['nullable', 'integer', 'min:0', 'max:2'],
            'form.apgar_activity_1min' => ['nullable', 'integer', 'min:0', 'max:2'],
            'form.apgar_respiration_1min' => ['nullable', 'integer', 'min:0', 'max:2'],
            'form.general_condition' => ['nullable', 'string', 'max:255'],
            'form.physical_examination' => ['nullable', 'string', 'max:5000'],
            'form.birth_defects_noted' => ['nullable', 'string', 'max:5000'],
            'form.meconium_aspiration' => ['nullable', 'string', 'max:5000'],
            'form.breastfeeding_initiated' => ['boolean'],
            'form.first_breastfeed_time' => ['nullable', 'date'],
            'form.feeding_problems' => ['nullable', 'string', 'max:5000'],
            'form.vitamin_k_given' => ['boolean'],
            'form.eye_prophylaxis_given' => ['boolean'],
            'form.immunizations_given' => ['boolean'],
            'form.immunizations_details' => ['nullable', 'string', 'max:5000'],
            'form.screening_test_done' => ['boolean'],
            'form.screening_test_results' => ['nullable', 'string', 'max:5000'],
            'form.special_care_needed' => ['nullable', 'string', 'max:5000'],
            'form.referred_to' => ['nullable', 'string', 'max:5000'],
            'form.status' => ['nullable', 'in:alive,stillborn,early_neonatal_death'],
            'form.neonatal_observations' => ['nullable', 'string', 'max:5000'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);

        $validated['delivery_id'] = $validated['delivery_id'] ?: null;
        $validated['birth_date_time'] = $validated['birth_date_time'] ?: now();
        $validated['sex'] = $validated['sex'] ?: 'female';
        $validated['status'] = $validated['status'] ?: 'alive';

        $newborn = Newborn::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'recorded_by' => auth()->id(),
            'newborn_registration_number' => (new Newborn())->generateRegistrationNumber(),
        ]);

        $this->logVisitActivity($visit, "Newborn recorded: {$newborn->newborn_registration_number}");
    }

    private function savePostnatal(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.delivery_id' => ['nullable', 'exists:deliveries,id'],
            'form.examination_date_time' => ['nullable', 'date'],
            'form.hours_post_delivery' => ['nullable', 'integer', 'min:0'],
            'form.examination_time' => ['nullable', 'in:immediate_0-2h,6-12h,24h,48h,day4_6,week1,week2,week6'],
            'form.blood_pressure' => ['nullable', 'string', 'max:50'],
            'form.pulse_rate' => ['nullable', 'string', 'max:50'],
            'form.temperature' => ['nullable', 'string', 'max:50'],
            'form.respiration_rate' => ['nullable', 'string', 'max:50'],
            'form.general_appearance' => ['nullable', 'string', 'max:5000'],
            'form.consciousness_level' => ['nullable', 'in:alert,drowsy,unconscious'],
            'form.skin_colour' => ['nullable', 'string', 'max:5000'],
            'form.uterine_size' => ['nullable', 'string', 'max:255'],
            'form.uterine_consistency' => ['nullable', 'string', 'max:255'],
            'form.uterine_tenderness' => ['nullable', 'string', 'max:255'],
            'form.fundal_height' => ['nullable', 'string', 'max:255'],
            'form.lochia_type' => ['nullable', 'string', 'max:255'],
            'form.lochia_amount' => ['nullable', 'string', 'max:255'],
            'form.lochia_odour' => ['nullable', 'string', 'max:255'],
            'form.clot_presence' => ['nullable', 'string', 'max:255'],
            'form.perineal_assessment' => ['nullable', 'string', 'max:5000'],
            'form.perineal_wound_status' => ['nullable', 'string', 'max:255'],
            'form.perineal_pain' => ['nullable', 'string', 'max:5000'],
            'form.vaginal_examination' => ['nullable', 'string', 'max:5000'],
            'form.breast_examination' => ['nullable', 'string', 'max:5000'],
            'form.nipple_condition' => ['nullable', 'string', 'max:5000'],
            'form.breast_engorgement' => ['nullable', 'string', 'max:5000'],
            'form.breast_milk_expression' => ['nullable', 'string', 'max:5000'],
            'form.breastfeeding_successful' => ['boolean'],
            'form.breastfeeding_problems' => ['nullable', 'string', 'max:5000'],
            'form.abdominal_examination' => ['nullable', 'string', 'max:5000'],
            'form.wound_assessment' => ['nullable', 'string', 'max:5000'],
            'form.drain_status' => ['nullable', 'string', 'max:5000'],
            'form.lower_limbs_examination' => ['nullable', 'string', 'max:5000'],
            'form.oedema_assessment' => ['nullable', 'string', 'max:5000'],
            'form.calf_tenderness' => ['nullable', 'string', 'max:5000'],
            'form.signs_of_dvt' => ['nullable', 'string', 'max:5000'],
            'form.maternal_mood' => ['nullable', 'string', 'max:5000'],
            'form.emotional_state' => ['nullable', 'string', 'max:5000'],
            'form.signs_of_depression' => ['boolean'],
            'form.bonding_with_baby' => ['nullable', 'string', 'max:5000'],
            'form.complications_identified' => ['nullable', 'string', 'max:5000'],
            'form.infection_signs' => ['nullable', 'string', 'max:5000'],
            'form.bleeding_assessment' => ['nullable', 'string', 'max:5000'],
            'form.hypertension_assessment' => ['nullable', 'string', 'max:5000'],
            'form.sleep_patterns' => ['nullable', 'string', 'max:5000'],
            'form.pain_level' => ['nullable', 'string', 'max:5000'],
            'form.activity_tolerance' => ['nullable', 'string', 'max:5000'],
            'form.perineal_care_ability' => ['nullable', 'string', 'max:5000'],
            'form.counseling_topics' => ['nullable', 'string', 'max:5000'],
            'form.contraception_discussed' => ['boolean'],
            'form.contraception_method_chosen' => ['nullable', 'string', 'max:5000'],
            'form.hygiene_taught' => ['boolean'],
            'form.danger_signs_explained' => ['boolean'],
            'form.recovery_status' => ['nullable', 'in:normal,complicated,needs_referral'],
            'form.clinical_summary' => ['nullable', 'string', 'max:5000'],
            'form.management_plan' => ['nullable', 'string', 'max:5000'],
            'form.medications_prescribed' => ['nullable', 'string', 'max:5000'],
            'form.follow_up_plan' => ['nullable', 'string', 'max:5000'],
            'form.next_follow_up_date' => ['nullable', 'date'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);

        $validated['delivery_id'] = $validated['delivery_id'] ?: null;
        $validated['examination_date_time'] = $validated['examination_date_time'] ?: now();
        $validated['recovery_status'] = $validated['recovery_status'] ?: 'normal';

        PostnatalExamination::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'recorded_by' => auth()->id(),
        ]);

        $this->logVisitActivity($visit, 'Postnatal examination recorded');
    }

    private function saveChildFollowUp(PatientVisit $visit): void
    {
        $validated = $this->validate([
            'form.newborn_id' => ['nullable', 'exists:newborns,id'],
            'form.follow_up_date_time' => ['nullable', 'date'],
            'form.days_of_life' => ['nullable', 'integer', 'min:0', 'max:3660'],
            'form.follow_up_period' => ['nullable', 'in:day_3,day_7,day_10,day_14,6weeks,3months,6months,year1'],
            'form.location' => ['nullable', 'in:hospital,clinic,home,other'],
            'form.location_details' => ['nullable', 'string', 'max:5000'],
            'form.weight' => ['nullable', 'string', 'max:50'],
            'form.feeding_type' => ['nullable', 'string', 'max:255'],
            'form.how_baby_is_feeding' => ['nullable', 'string', 'max:5000'],
            'form.mother_observations' => ['nullable', 'string', 'max:5000'],
            'form.temperature' => ['nullable', 'string', 'max:50'],
            'form.heart_rate' => ['nullable', 'string', 'max:50'],
            'form.respiratory_rate' => ['nullable', 'string', 'max:50'],
            'form.length' => ['nullable', 'string', 'max:50'],
            'form.head_circumference' => ['nullable', 'string', 'max:50'],
            'form.weight_percentile' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'form.weight_change_since_birth' => ['nullable', 'string', 'max:255'],
            'form.weight_gain_rate' => ['nullable', 'string', 'max:255'],
            'form.weight_assessment' => ['nullable', 'string', 'max:5000'],
            'form.general_appearance' => ['nullable', 'string', 'max:5000'],
            'form.activity_level' => ['nullable', 'string', 'max:5000'],
            'form.alertness' => ['nullable', 'string', 'max:5000'],
            'form.skin_examination' => ['nullable', 'string', 'max:5000'],
            'form.umbilical_cord_status' => ['nullable', 'string', 'max:5000'],
            'form.umbilical_discharge' => ['nullable', 'string', 'max:5000'],
            'form.signs_of_infection' => ['nullable', 'string', 'max:5000'],
            'form.jaundice_present' => ['nullable', 'string', 'max:255'],
            'form.jaundice_level' => ['nullable', 'string', 'max:5000'],
            'form.jaundice_management' => ['nullable', 'string', 'max:5000'],
            'form.breast_examination' => ['nullable', 'string', 'max:5000'],
            'form.latching_quality' => ['nullable', 'string', 'max:5000'],
            'form.suckling_pattern' => ['nullable', 'string', 'max:5000'],
            'form.milk_transfer' => ['nullable', 'string', 'max:5000'],
            'form.bottle_feeding_if_applicable' => ['nullable', 'string', 'max:5000'],
            'form.feeding_frequency' => ['nullable', 'string', 'max:255'],
            'form.feeding_duration' => ['nullable', 'string', 'max:255'],
            'form.feeding_problems' => ['nullable', 'string', 'max:5000'],
            'form.mother_nipple_problems' => ['nullable', 'string', 'max:5000'],
            'form.urinary_output' => ['nullable', 'string', 'max:5000'],
            'form.stool_output' => ['nullable', 'string', 'max:5000'],
            'form.stool_characteristics' => ['nullable', 'string', 'max:5000'],
            'form.elimination_problems' => ['nullable', 'string', 'max:5000'],
            'form.responsiveness' => ['nullable', 'string', 'max:5000'],
            'form.cry_quality' => ['nullable', 'string', 'max:5000'],
            'form.reflex_assessment' => ['nullable', 'string', 'max:5000'],
            'form.muscle_tone' => ['nullable', 'string', 'max:5000'],
            'form.immunizations_up_to_date' => ['boolean'],
            'form.immunizations_given' => ['nullable', 'string', 'max:5000'],
            'form.immunizations_planned' => ['nullable', 'string', 'max:5000'],
            'form.newborn_screening_done' => ['boolean'],
            'form.newborn_screening_results' => ['nullable', 'string', 'max:5000'],
            'form.hearing_screening_done' => ['boolean'],
            'form.hearing_screening_results' => ['nullable', 'string', 'max:5000'],
            'form.developmental_milestones' => ['nullable', 'string', 'max:5000'],
            'form.developmental_concerns' => ['nullable', 'string', 'max:5000'],
            'form.mother_recovery_status' => ['nullable', 'string', 'max:5000'],
            'form.mother_emotional_wellbeing' => ['nullable', 'string', 'max:5000'],
            'form.mother_breastfeeding_support' => ['nullable', 'string', 'max:5000'],
            'form.baby_concerns' => ['nullable', 'string', 'max:5000'],
            'form.mother_concerns' => ['nullable', 'string', 'max:5000'],
            'form.complications_identified' => ['nullable', 'string', 'max:5000'],
            'form.counseling_topics' => ['nullable', 'string', 'max:5000'],
            'form.infant_care_advice_given' => ['boolean'],
            'form.feeding_guidance_given' => ['boolean'],
            'form.cord_care_advice_given' => ['boolean'],
            'form.hygiene_safety_advice_given' => ['boolean'],
            'form.danger_signs_explained' => ['boolean'],
            'form.health_status' => ['nullable', 'in:normal,at_risk,needs_referral,referred'],
            'form.referral_reason' => ['nullable', 'string', 'max:5000'],
            'form.referral_destination' => ['nullable', 'string', 'max:5000'],
            'form.clinical_summary' => ['nullable', 'string', 'max:5000'],
            'form.management_plan' => ['nullable', 'string', 'max:5000'],
            'form.next_follow_up_date' => ['nullable', 'date'],
            'form.next_follow_up_reason' => ['nullable', 'string', 'max:5000'],
        ])['form'];
        $validated = $this->nullEmptyStrings($validated);

        $validated['newborn_id'] = $validated['newborn_id'] ?: null;
        $validated['follow_up_date_time'] = $validated['follow_up_date_time'] ?: now();
        $validated['location'] = $validated['location'] ?: 'hospital';
        $validated['health_status'] = $validated['health_status'] ?: 'normal';

        ChildFollowUp::create($validated + [
            'patient_id' => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'recorded_by' => auth()->id(),
        ]);

        $this->logVisitActivity($visit, 'Child follow-up recorded');
    }

    private function patients(): Collection
    {
        if (strlen(trim($this->search)) < 2) {
            return new Collection();
        }

        $search = trim($this->search);

        return Patient::with('demographic')
            ->where('hospital_number', 'like', "%{$search}%")
            ->orWhereHas('demographic', function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            })
            ->limit(8)
            ->get();
    }

    private function patient(): ?Patient
    {
        return $this->patientId ? Patient::with('demographic')->find($this->patientId) : null;
    }

    private function visit(): ?PatientVisit
    {
        return $this->visitId ? PatientVisit::with('patient.demographic')->find($this->visitId) : null;
    }

    private function activeVisit(?Patient $patient): ?PatientVisit
    {
        if (! $patient) {
            return null;
        }

        return $patient->patientVisits()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['Closed', 'closed', 'Discharged', 'discharged', 'Absconded', 'absconded']);
            })
            ->latest('created_at')
            ->first();
    }

    private function createMaternityVisit(Patient $patient): PatientVisit
    {
        return $patient->patientVisits()->create([
            'visit_date' => now(),
            'visit_type' => 'Maternity Care',
            'reason_for_visit' => 'Maternity activity',
            'clinical_notes' => 'Auto-created while recording maternity activity.',
            'status' => 'Active',
            'created_by' => auth()->id(),
        ]);
    }

    private function patientIsFemale(?Patient $patient): bool
    {
        return strtolower((string) $patient?->demographic?->gender) === 'female';
    }

    private function activityCounts(PatientVisit $visit): array
    {
        return [
            'antenatal' => $visit->antenatalCare()->count(),
            'labour' => $visit->labours()->count(),
            'delivery' => $visit->deliveries()->count(),
            'newborn' => $visit->newborns()->count(),
            'postnatal' => $visit->postnatalExaminations()->count(),
            'child_follow_up' => $visit->childFollowUps()->count(),
        ];
    }

    private function resetForm(): void
    {
        $this->resetErrorBag();

        $this->form = match ($this->activity) {
            'antenatal' => [
                'last_menstrual_period' => '',
                'expected_delivery_date' => '',
                'gestational_weeks' => '',
                'number_of_fetuses' => 1,
                'pregnancy_type' => '',
                'blood_pressure' => '',
                'weight' => '',
                'height' => '',
                'abdominal_examination' => '',
                'fundal_height' => '',
                'fetal_heart_rate' => '',
                'fetal_movement' => '',
                'vaginal_examination' => '',
                'urine_analysis' => '',
                'blood_tests' => '',
                'ultrasound_findings' => '',
                'risk_factors' => '',
                'complications' => '',
                'management_plan' => '',
                'counseling_topics' => '',
                'took_supplements' => false,
                'status' => '',
                'clinical_notes' => '',
            ],
            'labour' => [
                'labour_onset_time' => '',
                'mode_of_onset' => '',
                'reason_for_induction' => '',
                'gestational_weeks' => '',
                'labour_type' => '',
                'previous_obstetric_history' => '',
                'cervical_state' => '',
                'show' => '',
                'rupture_of_membranes' => '',
                'liquor' => '',
                'blood_pressure' => '',
                'pulse_rate' => '',
                'temperature' => '',
                'respiration_rate' => '',
                'stage' => '',
                'first_stage_started_at' => '',
                'second_stage_started_at' => '',
                'third_stage_started_at' => '',
                'fetal_heart_rate' => '',
                'fetal_monitoring_notes' => '',
                'complications' => '',
                'status' => '',
                'clinical_notes' => '',
            ],
            'delivery' => [
                'labour_id' => '',
                'delivery_date_time' => '',
                'delivery_type' => '',
                'reason_for_delivery_type' => '',
                'assisted_with' => '',
                'indication_for_assistance' => '',
                'caesarean_type' => '',
                'indication_for_caesarean' => '',
                'perineal_trauma' => '',
                'episiotomy' => '',
                'perineal_repair' => '',
                'placenta_delivery_method' => '',
                'placenta_delivered_at' => '',
                'placental_examination' => '',
                'estimated_blood_loss' => '',
                'blood_loss_assessment' => '',
                'uterine_tone' => '',
                'per_vaginal_bleeding' => '',
                'blood_pressure' => '',
                'pulse_rate' => '',
                'general_condition' => '',
                'complications' => '',
                'management_of_complications' => '',
                'number_of_babies' => '',
                'delivery_status' => '',
                'delivery_summary' => '',
            ],
            'newborn' => [
                'delivery_id' => '',
                'sex' => '',
                'birth_order' => '',
                'birth_date_time' => '',
                'birth_weight' => '',
                'birth_length' => '',
                'head_circumference' => '',
                'presentation' => '',
                'delivery_notes' => '',
                'apgar_score_1_minute' => '',
                'apgar_score_5_minutes' => '',
                'apgar_score_10_minutes' => '',
                'apgar_appearance_1min' => '',
                'apgar_pulse_1min' => '',
                'apgar_grimace_1min' => '',
                'apgar_activity_1min' => '',
                'apgar_respiration_1min' => '',
                'general_condition' => '',
                'physical_examination' => '',
                'birth_defects_noted' => '',
                'meconium_aspiration' => '',
                'breastfeeding_initiated' => false,
                'first_breastfeed_time' => '',
                'feeding_problems' => '',
                'vitamin_k_given' => false,
                'eye_prophylaxis_given' => false,
                'immunizations_given' => false,
                'immunizations_details' => '',
                'screening_test_done' => false,
                'screening_test_results' => '',
                'special_care_needed' => '',
                'referred_to' => '',
                'status' => '',
                'neonatal_observations' => '',
            ],
            'postnatal' => [
                'delivery_id' => '',
                'examination_date_time' => '',
                'hours_post_delivery' => '',
                'examination_time' => '',
                'blood_pressure' => '',
                'pulse_rate' => '',
                'temperature' => '',
                'respiration_rate' => '',
                'general_appearance' => '',
                'consciousness_level' => '',
                'skin_colour' => '',
                'uterine_size' => '',
                'uterine_consistency' => '',
                'uterine_tenderness' => '',
                'fundal_height' => '',
                'lochia_type' => '',
                'lochia_amount' => '',
                'lochia_odour' => '',
                'clot_presence' => '',
                'perineal_assessment' => '',
                'perineal_wound_status' => '',
                'perineal_pain' => '',
                'vaginal_examination' => '',
                'breast_examination' => '',
                'nipple_condition' => '',
                'breast_engorgement' => '',
                'breast_milk_expression' => '',
                'breastfeeding_successful' => false,
                'breastfeeding_problems' => '',
                'abdominal_examination' => '',
                'wound_assessment' => '',
                'drain_status' => '',
                'lower_limbs_examination' => '',
                'oedema_assessment' => '',
                'calf_tenderness' => '',
                'signs_of_dvt' => '',
                'maternal_mood' => '',
                'emotional_state' => '',
                'signs_of_depression' => false,
                'bonding_with_baby' => '',
                'complications_identified' => '',
                'infection_signs' => '',
                'bleeding_assessment' => '',
                'hypertension_assessment' => '',
                'sleep_patterns' => '',
                'pain_level' => '',
                'activity_tolerance' => '',
                'perineal_care_ability' => '',
                'counseling_topics' => '',
                'contraception_discussed' => false,
                'contraception_method_chosen' => '',
                'hygiene_taught' => false,
                'danger_signs_explained' => false,
                'recovery_status' => '',
                'clinical_summary' => '',
                'management_plan' => '',
                'medications_prescribed' => '',
                'follow_up_plan' => '',
                'next_follow_up_date' => '',
            ],
            'child_follow_up' => [
                'newborn_id' => '',
                'follow_up_date_time' => '',
                'days_of_life' => '',
                'follow_up_period' => '',
                'location' => '',
                'location_details' => '',
                'weight' => '',
                'feeding_type' => '',
                'how_baby_is_feeding' => '',
                'mother_observations' => '',
                'temperature' => '',
                'heart_rate' => '',
                'respiratory_rate' => '',
                'length' => '',
                'head_circumference' => '',
                'weight_percentile' => '',
                'weight_change_since_birth' => '',
                'weight_gain_rate' => '',
                'weight_assessment' => '',
                'general_appearance' => '',
                'activity_level' => '',
                'alertness' => '',
                'skin_examination' => '',
                'umbilical_cord_status' => '',
                'umbilical_discharge' => '',
                'signs_of_infection' => '',
                'jaundice_present' => '',
                'jaundice_level' => '',
                'jaundice_management' => '',
                'breast_examination' => '',
                'latching_quality' => '',
                'suckling_pattern' => '',
                'milk_transfer' => '',
                'bottle_feeding_if_applicable' => '',
                'feeding_frequency' => '',
                'feeding_duration' => '',
                'feeding_problems' => '',
                'mother_nipple_problems' => '',
                'urinary_output' => '',
                'stool_output' => '',
                'stool_characteristics' => '',
                'elimination_problems' => '',
                'responsiveness' => '',
                'cry_quality' => '',
                'reflex_assessment' => '',
                'muscle_tone' => '',
                'immunizations_up_to_date' => false,
                'immunizations_given' => '',
                'immunizations_planned' => '',
                'newborn_screening_done' => false,
                'newborn_screening_results' => '',
                'hearing_screening_done' => false,
                'hearing_screening_results' => '',
                'developmental_milestones' => '',
                'developmental_concerns' => '',
                'mother_recovery_status' => '',
                'mother_emotional_wellbeing' => '',
                'mother_breastfeeding_support' => '',
                'baby_concerns' => '',
                'mother_concerns' => '',
                'complications_identified' => '',
                'counseling_topics' => '',
                'infant_care_advice_given' => false,
                'feeding_guidance_given' => false,
                'cord_care_advice_given' => false,
                'hygiene_safety_advice_given' => false,
                'danger_signs_explained' => false,
                'health_status' => '',
                'referral_reason' => '',
                'referral_destination' => '',
                'clinical_summary' => '',
                'management_plan' => '',
                'next_follow_up_date' => '',
                'next_follow_up_reason' => '',
            ],
            default => [],
        };
    }

    private function logVisitActivity(PatientVisit $visit, string $activity): void
    {
        $visit->visitActivities()->create([
            'activity' => $activity,
            'recorded_by' => auth()->id(),
        ]);
    }

    private function nullEmptyStrings(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function resolveRoutePatient(?Patient &$patient): void
    {
        if ($patient?->exists || ! request()->filled('patient')) {
            return;
        }

        $patient = Patient::find(request('patient'));
    }
}

