<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Investigation;
use App\Models\InvestigationRequest;
use App\Models\Medicine;
use App\Models\PatientReferral;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\SpecialistConsultation;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\SpecialistSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpecialistWorkflow
{
    public function __construct(private SpecialistAccess $access, private PackageCapabilities $capabilities) {}

    public function audit(string $action, $model, array $meta = []): void
    {
        AuditLog::create(['actor_id' => auth()->id(), 'action' => 'specialist.'.$action, 'model_type' => get_class($model), 'model_id' => $model->id,
            'meta' => $meta, 'ip' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    public function book(array $data): SpecialistConsultation
    {
        $user = $this->access->require('specialist_appointment.manage');
        $data = Validator::make($data, [
            'patient_id' => ['required', 'integer'], 'profile_id' => ['required', 'integer'], 'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'mode' => ['required', Rule::in(['physical', 'online'])],
            'reason' => ['required', 'string', 'max:2000'], 'token' => ['required', 'uuid'], 'walkin' => ['nullable', 'boolean'],
            'followup_id' => ['nullable', 'integer'], 'patient_visit_id' => ['nullable', 'integer'],
        ])->validate();
        $this->access->patients($user)->findOrFail($data['patient_id']);

        return DB::transaction(function () use ($data) {
            $profile = SpecialistProfile::where('is_active', true)->lockForUpdate()->findOrFail($data['profile_id']);
            if ($existing = SpecialistConsultation::where('booking_token', $data['token'])->first()) {
                $this->check((int) $existing->patient_id === (int) $data['patient_id'], 'Booking token already used.');

                return $existing;
            }
            $this->check($profile->user && $profile->user->hasPermission('specialist_consultation.update') && app(LicenseService::class)->userHasModuleAccess($profile->user, 'specialist'), 'The selected specialist no longer has clinical access.');
            $offering = SpecialistService::with('service', 'specialty')->where('service_id', $data['service_id'])->firstOrFail();
            $this->check($offering->service?->is_active && $offering->specialty?->is_active
                && $profile->specialties()->whereKey($offering->specialty_id)->exists(), 'This service is not available with this specialist.');
            $settings = SpecialistSetting::find(1);
            $this->check(! ($data['walkin'] ?? false) || ($settings?->allow_walkins ?? true), 'Walk-in consultations are disabled.');
            $this->check(($profile->{$data['mode']}) && (! $settings || $settings->operating_mode === 'hybrid' || $settings->operating_mode === $data['mode']), 'Consultation mode is unavailable.');
            $this->check($data['mode'] !== 'physical' || filled($profile->location), 'Configure the consultation location first.');
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], $profile->timezone);
            $this->check($start->format('Y-m-d\TH:i') === $data['starts_at'], 'Invalid local time.');
            $end = $start->addMinutes($offering->duration_minutes);
            $availability = $profile->availability ?? [];
            $this->check(in_array($start->isoWeekday(), $availability['days'] ?? [])
                && $start->format('H:i') >= ($availability['from'] ?? '09:00')
                && $end->format('H:i') <= ($availability['to'] ?? '17:00') && $start->isSameDay($end)
                && ! in_array($start->toDateString(), $availability['unavailable_dates'] ?? []), 'The selected time is outside specialist availability.');
            $this->check($start->utc()->gte(now()->subMinutes(5)), 'Choose a current or future appointment.');
            $conflict = Appointment::where('specialist_profile_id', $profile->id)->where('status', 'Scheduled')
                ->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists();
            $this->check(! $conflict, 'This specialist already has a booking at that time.');
            $appointment = Appointment::create(['patient_id' => $data['patient_id'], 'appointment_date' => $start->toDateString(),
                'appointment_time' => $start->format('H:i:s'), 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'timezone' => $profile->timezone,
                'specialist_profile_id' => $profile->id, 'status' => 'Scheduled', 'notes' => $data['reason'], 'scheduled_by' => auth()->id()]);
            if (! empty($data['patient_visit_id'])) {
                PatientVisit::where('patient_id', $data['patient_id'])->where('status', 'Active')->findOrFail($data['patient_visit_id']);
            }
            $consultation = SpecialistConsultation::create(['booking_token' => $data['token'], 'patient_id' => $data['patient_id'], 'patient_visit_id' => $data['patient_visit_id'] ?? null,
                'appointment_id' => $appointment->id, 'specialist_profile_id' => $profile->id, 'specialty_id' => $offering->specialty_id,
                'service_id' => $offering->service_id, 'service_name' => $offering->service->name, 'specialist_name' => $profile->user->name, 'specialty_name' => $offering->specialty->name, 'charge' => $offering->service->price,
                'mode' => $data['mode'], 'location' => $data['mode'] === 'physical' ? $profile->location : 'Online', 'status' => 'scheduled']);
            $this->access->grant($consultation->patient_id, $profile->user_id);
            if (! empty($data['followup_id'])) {
                $origin = $this->access->consultation($data['followup_id'], 'specialist_appointment.manage');
                $this->check($origin->patient_id === $consultation->patient_id, 'Follow-up must belong to this patient.');
                $origin->update(['followup_consultation_id' => $consultation->id]);
            }
            $this->audit('appointment_created', $consultation);

            return $consultation;
        }, 3);
    }

    public function change(int $id, string $permission, callable $callback): mixed
    {
        $this->access->consultation($id, $permission);

        return DB::transaction(function () use ($id, $callback) {
            $c = SpecialistConsultation::lockForUpdate()->findOrFail($id);

            return $callback($c);
        }, 3);
    }

    public function start(int $id): void
    {
        $this->change($id, 'specialist_consultation.update', function ($c) {
            if ($c->status === 'in_progress') {
                return;
            }
            $this->check($c->status === 'scheduled', 'Only a scheduled consultation can be started.');
            $visit = $c->patient_visit_id
                ? PatientVisit::where('patient_id', $c->patient_id)->where('status', 'Active')->lockForUpdate()->findOrFail($c->patient_visit_id)
                : PatientVisit::create(['specialist_origin' => true, 'patient_id' => $c->patient_id, 'visit_date' => now(), 'visit_type' => 'Specialist',
                    'reason_for_visit' => $c->appointment->notes, 'status' => 'Active', 'created_by' => auth()->id()]);
            $c->update(['patient_visit_id' => $visit->id, 'status' => 'in_progress', 'started_at' => now()]);
            $this->audit('consultation_started', $c);
        });
    }

    public function saveClinical(int $id, array $data): void
    {
        $data = Validator::make($data, [
            'version' => ['required', 'integer'], 'complaint' => ['required', 'string', 'max:10000'],
            'history' => ['nullable', 'string', 'max:20000'], 'allergies' => ['nullable', 'string', 'max:5000'],
            'conditions' => ['nullable', 'string', 'max:5000'], 'examination' => ['nullable', 'string', 'max:20000'],
            'diagnosis' => ['required', 'string', 'max:10000'], 'plan' => ['required', 'string', 'max:20000'],
            'followup_due' => ['nullable', 'date', 'after_or_equal:today'], 'complete' => ['nullable', 'boolean'],
        ])->validate();
        $this->change($id, 'specialist_consultation.update', function ($c) use ($data) {
            $this->check($c->status === 'in_progress', 'Only an in-progress consultation can be edited.');
            $this->check($c->version === (int) $data['version'], 'This consultation changed. Reload before saving.');
            $complete = (bool) ($data['complete'] ?? false);
            if ($complete) {
                $this->access->require('specialist_consultation.complete');
            }
            $clinical = collect($data)->only(['complaint', 'history', 'allergies', 'conditions', 'examination', 'diagnosis', 'plan'])->all();
            $c->update(['clinical' => $clinical, 'version' => $c->version + 1, 'followup_due' => $data['followup_due'] ?? null,
                'status' => $complete ? 'completed' : 'in_progress', 'completed_by' => $complete ? auth()->id() : null, 'completed_name' => $complete ? auth()->user()->name : null, 'completed_at' => $complete ? now() : null]);
            if ($complete) {
                $c->appointment->update(['status' => 'Completed']);
            }
            $this->audit($complete ? 'consultation_completed' : 'clinical_updated', $c, ['version' => $c->version]);
        });
    }

    public function cancel(int $id, string $reason): void
    {
        $this->check(trim($reason) !== '' && mb_strlen($reason) <= 2000, 'Enter a cancellation reason (up to 2000 characters).');
        $this->change($id, 'specialist_appointment.manage', function ($c) use ($reason) {
            $this->check($c->status === 'scheduled', 'Only scheduled appointments can be cancelled.');
            $c->appointment->update(['status' => 'Cancelled', 'cancelled_date' => now(), 'cancellation_reason' => $reason]);
            $c->update(['status' => 'cancelled']);
            $this->audit('appointment_cancelled', $c);
        });
    }

    public function reschedule(int $id, array $data): void
    {
        $data = Validator::make($data, ['starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'reason' => ['required', 'string', 'max:2000']])->validate();
        $original = $this->access->consultation($id, 'specialist_appointment.manage');
        DB::transaction(function () use ($original, $data) {
            $profile = SpecialistProfile::where('is_active', true)->lockForUpdate()->findOrFail($original->specialist_profile_id);
            $c = SpecialistConsultation::lockForUpdate()->findOrFail($original->id);
            $this->check($c->status === 'scheduled', 'Only scheduled appointments can be moved.');
            $appointment = $c->appointment;
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], $profile->timezone);
            $duration = $appointment->starts_at->diffInMinutes($appointment->ends_at);
            $end = $start->addMinutes($duration);
            $a = $profile->availability ?? [];
            $this->check($start->format('Y-m-d\TH:i') === $data['starts_at'] && $start->utc()->gte(now()) && $start->isSameDay($end)
                && in_array($start->isoWeekday(), $a['days'] ?? []) && $start->format('H:i') >= ($a['from'] ?? '09:00')
                && $end->format('H:i') <= ($a['to'] ?? '17:00') && ! in_array($start->toDateString(), $a['unavailable_dates'] ?? []), 'Choose a valid available time.');
            $this->check(! Appointment::where('specialist_profile_id', $profile->id)->whereKeyNot($appointment->id)->where('status', 'Scheduled')
                ->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists(), 'This slot is already booked.');
            $before = $appointment->starts_at->toIso8601String();
            $appointment->update(['starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'timezone' => $profile->timezone,
                'appointment_date' => $start->toDateString(), 'appointment_time' => $start->format('H:i:s')]);
            $this->audit('appointment_rescheduled', $c, ['previous' => $before, 'new' => $start->utc()->toIso8601String(), 'reason' => $data['reason']]);
        }, 3);
    }

    public function amend(int $id, array $data): void
    {
        $data = Validator::make($data, ['reason' => ['required', 'string', 'max:2000'], 'content' => ['required', 'string', 'max:20000']])->validate();
        $this->change($id, 'specialist_consultation.amend', function ($c) use ($data) {
            $this->check($c->status === 'completed', 'Complete the consultation before recording an amendment.');
            $c->amendments()->create($data + ['user_id' => auth()->id(), 'author_name' => auth()->user()->name]);
            $this->audit('consultation_amended', $c);
        });
    }

    public function carePlan(int $id, array $data): void
    {
        $data = Validator::make($data, ['version' => ['required', 'integer', 'min:0'], 'problem' => ['required', 'string', 'max:5000'],
            'objectives' => ['required', 'string', 'max:10000'], 'instructions' => ['required', 'string', 'max:20000'],
            'review_date' => ['required', 'date'], 'status' => ['required', Rule::in(['active', 'completed', 'cancelled'])]])->validate();
        $this->change($id, 'specialist_care_plan.manage', function ($c) use ($data) {
            $this->check(in_array($c->status, ['in_progress', 'completed']), 'Start the consultation first.');
            $version = (int) $c->carePlans()->max('version');
            $this->check($version === (int) $data['version'], 'Care plan changed. Reload before saving.');
            $c->carePlans()->create(array_replace($data, ['version' => $version + 1, 'created_by' => auth()->id()]));
            $this->audit('care_plan_revised', $c, ['version' => $version + 1]);
        });
    }

    public function bill(int $id): Bill
    {
        return $this->change($id, 'specialist_billing.create', function ($c) {
            if ($c->bill_id) {
                return $c->bill;
            }
            $this->check(in_array($c->status, ['in_progress', 'completed']), 'Start the consultation before billing.');
            $bill = $this->createBill($c, $c->charge, $c->service_name);
            $bill->billServices()->create(['service_id' => $c->service_id, 'quantity' => 1, 'unit_price' => $c->charge, 'subtotal' => $c->charge]);
            ServiceRequest::create(['patient_visit_id' => $c->patient_visit_id, 'service_id' => $c->service_id, 'requested_by' => auth()->id(),
                'bill_id' => $bill->id, 'clinical_diagnoses' => 'Specialist service', 'requested_at' => now(), 'status' => 'pending', 'payment_status' => 'pending']);
            $c->update(['bill_id' => $bill->id]);
            $this->audit('bill_created', $c, ['bill_id' => $bill->id]);

            return $bill;
        });
    }

    private function createBill($c, $amount, string $label, ?int $department = null): Bill
    {
        return Bill::create(['specialist_consultation_id' => $c->id, 'patient_visit_id' => $c->patient_visit_id, 'department_id' => $department ?? Service::withTrashed()->find($c->service_id)?->department_id,
            'bill_number' => 'SBL'.Str::ulid(), 'service_description' => mb_substr($label, 0, 255), 'amount' => $amount, 'due_amount' => $amount,
            'status' => (float) $amount === 0.0 ? 'paid' : 'pending', 'issued_by' => auth()->id(), 'issued_date' => now(), 'due_date' => now(), 'notes' => 'Specialist consultation #'.$c->id]);
    }

    public function pay(int $id, array $data): Payment
    {
        $data = Validator::make($data, ['token' => ['required', 'uuid'], 'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)], 'reference_number' => ['nullable', 'string', 'max:255']])->validate();

        return $this->change($id, 'specialist_payment.record', function ($c) use ($data) {
            $this->check((bool) $c->bill_id, 'Create the bill before recording payment.');
            $bill = Bill::lockForUpdate()->findOrFail($c->bill_id);
            if ($existing = Payment::where('specialist_token', $data['token'])->first()) {
                $this->check($existing->bill_id === $bill->id, 'Payment token already used.');

                return $existing;
            }
            $this->check(in_array($bill->status, ['pending', 'partial']) && (float) $data['amount'] <= round((float) $bill->balance, 2), 'Payment exceeds the outstanding balance or bill is closed.');
            $payment = Payment::create(['specialist_token' => $data['token'], 'bill_id' => $bill->id,
                'payment_id' => 'SPY'.Str::ulid(), 'amount' => $data['amount'], 'payment_method_id' => $data['payment_method_id'],
                'reference_number' => $data['reference_number'] ?? null, 'status' => 'completed', 'paid_by' => auth()->id(), 'payment_date' => now()]);
            $bill->update(['status' => (float) $bill->fresh()->balance <= 0 ? 'paid' : 'partial']);
            $bill->serviceRequests()->update(['payment_status' => $bill->status]);
            $this->audit('payment_recorded', $c, ['payment_id' => $payment->id]);

            return $payment;
        });
    }

    public function prescribe(int $id, array $data): Prescription
    {
        $data = Validator::make($data, [
            'token' => ['required', 'uuid'], 'fulfillment' => ['required', Rule::in(['external', 'internal'])],
            'diagnosis' => ['required', 'string', 'max:5000'], 'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:medicines,id'], 'items.*.route_id' => ['required', 'integer', 'exists:routes,id'],
            'items.*.dosage' => ['required', 'string', 'max:255'], 'items.*.period' => ['required', 'string', 'max:255'], 'items.*.duration' => ['required', 'string', 'max:255'],
        ])->validate();

        return $this->change($id, 'specialist_prescription.issue', function ($c) use ($data) {
            $this->check($c->status === 'in_progress', 'Issue prescriptions during the consultation.');
            if ($existing = $c->prescriptions()->where('specialist_token', $data['token'])->first()) {
                return $existing;
            }
            $this->check($data['fulfillment'] === 'external' || $this->capabilities->available('dispensing'), 'Internal pharmacy is unavailable. Choose external fulfillment.');
            $prescription = $c->prescriptions()->create(['specialist_token' => $data['token'], 'patient_visit_id' => $c->patient_visit_id,
                'prescribe_by' => auth()->id(), 'treatment_diagnosis' => $data['diagnosis'], 'fulfillment' => $data['fulfillment'],
                'status' => $data['fulfillment'] === 'internal' ? 'submitted' : 'issued_external']);
            foreach ($data['items'] as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);
                $prescription->prescriptionItems()->create($item + ['medicine_name' => $medicine->name]);
            }
            $this->audit('prescription_issued', $c, ['prescription_id' => $prescription->id, 'fulfillment' => $data['fulfillment']]);

            return $prescription;
        });
    }

    public function investigate(int $id, array $data): InvestigationRequest
    {
        $data = Validator::make($data, [
            'token' => ['required', 'uuid'], 'destination' => ['required', Rule::in(['external', 'laboratory', 'imaging'])],
            'investigation_id' => ['nullable', 'integer', 'exists:investigations,id'], 'name' => ['required', 'string', 'max:255'],
            'clinical_diagnoses' => ['required', 'string', 'max:10000'], 'external_destination' => ['nullable', 'string', 'max:255'],
            'partner_id' => ['nullable', Rule::exists('collaboration_partners', 'id')->where('is_active', true)],
        ])->validate();

        return $this->change($id, 'specialist_investigation.request', function ($c) use ($data) {
            $this->check($c->status === 'in_progress', 'Request investigations during the consultation.');
            if ($existing = $c->investigations()->where('specialist_token', $data['token'])->first()) {
                return $existing;
            }
            $external = $data['destination'] === 'external';
            $bill = null;
            $investigation = null;
            if (! $external) {
                $this->check($this->capabilities->available($data['destination']), 'This diagnostic capability is unavailable.');
                $investigation = Investigation::with('investigationType.department')->findOrFail($data['investigation_id'] ?? 0);
                $department = strtolower((string) $investigation->investigationType?->department?->name);
                $this->check($data['destination'] === 'laboratory' ? str_contains($department, 'lab') : (str_contains($department, 'radiolog') || str_contains($department, 'imag')), 'Select a test belonging to the chosen diagnostic department.');
                $bill = $this->createBill($c, $investigation->price, $investigation->name, $investigation->investigationType?->department_id);
                $bill->billInvestigations()->create(['investigation_id' => $investigation->id, 'quantity' => 1, 'unit_price' => $investigation->price, 'subtotal' => $investigation->price]);
            } else {
                $this->check(filled($data['external_destination'] ?? null) || ! empty($data['partner_id']), 'Provide the external facility or approved partner.');
            }
            $request = $c->investigations()->create(['specialist_token' => $data['token'], 'patient_visit_id' => $c->patient_visit_id,
                'investigation_id' => $investigation?->id, 'requested_by' => auth()->id(), 'bill_id' => $bill?->id, 'is_external' => $external,
                'requested_name' => $investigation?->name ?? $data['name'], 'clinical_diagnoses' => $data['clinical_diagnoses'],
                'requested_at' => now(), 'status' => 'Pending', 'external_destination' => $external ? ($data['external_destination'] ?? null) : null,
                'collaboration_partner_id' => $external ? ($data['partner_id'] ?? null) : null]);
            if (! $external) {
                $request->getLabNo();
            }
            $this->audit('investigation_requested', $c, ['request_id' => $request->id, 'external' => $external]);

            return $request;
        });
    }

    public function reviewResult(int $id, int $requestId, string $summary): void
    {
        $this->check(trim($summary) !== '' && mb_strlen($summary) <= 10000, 'Enter a review summary (up to 10000 characters).');
        $this->change($id, 'specialist_result.review', function ($c) use ($requestId, $summary) {
            $request = $c->investigations()->findOrFail($requestId);
            $this->check($request->routing_method !== 'partner', 'Review partner submissions in the Care Network case.');
            if ($request->is_external) {
                $this->check($c->documents()->where('investigation_request_id', $requestId)->exists(), 'Attach the external report before review.');
            } else {
                $this->check($request->completed_at || strtolower($request->status) === 'completed', 'The diagnostic result is not complete.');
            }
            $this->check(! $request->reviewed_at, 'This result has already been reviewed. Record further commentary as a consultation amendment.');
            $request->update(['reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'external_summary' => $summary]);
            $this->audit('result_reviewed', $c, ['request_id' => $request->id]);
        });
    }

    public function refer(int $id, array $data): PatientReferral
    {
        $data = Validator::make($data, [
            'token' => ['required', 'uuid'], 'destination_type' => ['required', Rule::in(['external', 'specialist', 'hospital', 'clinic', 'maternity'])],
            'destination' => ['required', 'string', 'max:255'], 'destination_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'partner_id' => ['nullable', Rule::exists('collaboration_partners', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'max:10000'], 'summary' => ['required', 'string', 'max:20000'],
            'urgency' => ['required', Rule::in(['routine', 'urgent', 'emergency'])],
        ])->validate();

        return $this->change($id, 'specialist_referral.create', function ($c) use ($data) {
            $this->check(in_array($c->status, ['in_progress', 'completed']), 'Start the consultation before referring.');
            if ($existing = $c->referrals()->where('specialist_token', $data['token'])->first()) {
                return $existing;
            }
            if ($data['destination_type'] === 'hospital') {
                $this->check($this->capabilities->available('inpatient'), 'Hospital care is unavailable; use an external referral.');
            }
            if ($data['destination_type'] === 'maternity') {
                $this->check($this->capabilities->available('maternity'), 'Maternity is unavailable; use an external referral.');
            }
            if ($data['destination_type'] === 'clinic') {
                $this->check(app(LicenseService::class)->moduleEnabled('doctor'), 'Internal clinic is unavailable.');
            }
            if ($data['destination_type'] !== 'external') {
                $this->check(! empty($data['destination_user_id']), 'Select the receiving staff member.');
                $receiver = User::findOrFail($data['destination_user_id']);
                $this->check($receiver->hasPermission('specialist_referral.accept') && app(LicenseService::class)->userHasModuleAccess($receiver, 'specialist'), 'The recipient has not been granted Specialist referral receiving access.');
                if ($data['destination_type'] === 'specialist') {
                    $this->check(SpecialistProfile::where('user_id', $receiver->id)->where('is_active', true)->exists(), 'Select an active specialist.');
                }
            }
            $referral = $c->referrals()->create(['specialist_token' => $data['token'], 'patient_id' => $c->patient_id, 'referral_date' => now(),
                'referred_to_department' => $data['destination'], 'reason_for_referral' => $data['reason'], 'notes' => $data['summary'],
                'status' => 'Pending', 'referred_by' => auth()->id(), 'destination_type' => $data['destination_type'],
                'destination_user_id' => $data['destination_type'] === 'external' ? null : $data['destination_user_id'],
                'collaboration_partner_id' => $data['partner_id'] ?? null, 'urgency' => $data['urgency']]);
            $this->audit('referral_sent', $c, ['referral_id' => $referral->id]);

            return $referral;
        });
    }

    public function respondReferral(int $id, array $data): void
    {
        $user = $this->access->require('specialist_referral.accept');
        $data = Validator::make($data, ['status' => ['required', Rule::in(['Accepted', 'Rejected', 'Completed'])],
            'outcome' => ['required', 'string', 'max:10000'], 'admission_id' => ['nullable', 'integer', 'exists:admissions,id']])->validate();
        DB::transaction(function () use ($id, $data, $user) {
            $r = PatientReferral::where('destination_user_id', $user->id)->whereNotNull('specialist_consultation_id')->lockForUpdate()->findOrFail($id);
            $this->check(($r->status === 'Pending' && in_array($data['status'], ['Accepted', 'Rejected'])) || ($r->status === 'Accepted' && $data['status'] === 'Completed'), 'Invalid referral transition.');
            if (! empty($data['admission_id'])) {
                $this->check($r->destination_type === 'hospital' && $this->capabilities->available('inpatient'), 'Admission outcome only applies to hospital referrals.');
                $admission = Admission::findOrFail($data['admission_id']);
                $this->check((int) $admission->patientVisit?->patient_id === (int) $r->patient_id, 'Admission belongs to another patient.');
            }
            $r->update(['status' => $data['status'], 'outcome' => $data['outcome'], 'outcome_admission_id' => $data['admission_id'] ?? null,
                'accepted_date' => $data['status'] === 'Accepted' ? now() : $r->accepted_date, 'completed_date' => $data['status'] === 'Completed' ? now() : null]);
            if ($data['status'] === 'Accepted') {
                $this->access->grant($r->patient_id, $user->id);
            }
            $this->audit('referral_responded', $r, ['status' => $data['status']]);
        });
    }

    public function externalOutcome(int $id, int $referralId, array $data): void
    {
        $data = Validator::make($data, ['status' => ['required', Rule::in(['Accepted', 'Rejected', 'Completed'])], 'outcome' => ['required', 'string', 'max:10000']])->validate();
        $this->change($id, 'specialist_referral.create', function ($c) use ($referralId, $data) {
            $r = $c->referrals()->where('destination_type', 'external')->findOrFail($referralId);
            $this->check($r->routing_method !== 'partner', 'Record partner outcomes in the Care Network case.');
            $this->check(($r->status === 'Pending' && in_array($data['status'], ['Accepted', 'Rejected'])) || ($r->status === 'Accepted' && $data['status'] === 'Completed'), 'Invalid referral transition.');
            $r->update(['status' => $data['status'], 'outcome' => $data['outcome'],
                'accepted_date' => $data['status'] === 'Accepted' ? now() : $r->accepted_date, 'completed_date' => $data['status'] === 'Completed' ? now() : null]);
            $this->audit('external_referral_outcome_recorded', $r, ['status' => $data['status']]);
        });
    }
}
