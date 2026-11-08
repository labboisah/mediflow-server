<?php

namespace App\Services\PartnerNetwork;

use App\Models\CollaborationPartner;
use App\Models\InvestigationRequest;
use App\Models\PartnerCaseEvent;
use App\Models\PartnerCollaboration;
use App\Models\PartnerDisclosure;
use App\Models\PartnerMembership;
use App\Models\PartnerOutbox;
use App\Models\PartnerService;
use App\Models\Partnership;
use App\Models\PartnerSubmission;
use App\Models\PatientReferral;
use App\Services\SpecialistAccess;
use App\Services\SpecialistWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Collaboration
{
    public function __construct(public Access $access, public Workflow $workflow) {}

    public function dispatch(int $consultation, array $input): PartnerCollaboration
    {
        $u = $this->access->staff('partner_collaboration.create');
        $d = Validator::make($input, ['partnership_id' => ['required', 'integer'], 'service_id' => ['required', 'integer'], 'kind' => ['required', Rule::in(['diagnostic', 'hospital', 'specialist'])], 'token' => ['required', 'uuid'], 'summary' => ['required', 'string', 'max:20000'], 'purpose' => ['required', 'string', 'max:2000'], 'authorization_basis' => ['required', 'string', 'max:5000'], 'expires_at' => ['required', 'date', 'after:now'], 'confirm_disclosure' => ['accepted']])->validate();
        $c = app(SpecialistAccess::class)->consultation($consultation, 'specialist_consultation.read');

        return DB::transaction(function () use ($u, $d, $c) {
            $p = Partnership::lockForUpdate()->findOrFail($d['partnership_id']);
            $a = $this->access->agreement($p);
            if ($old = PartnerCollaboration::where('token', $d['token'])->first()) {
                $this->workflow->check($old->consultation_id === $c->id && $old->partnership_id === $p->id, 'Dispatch token already used.');

                return $old;
            }
            $this->workflow->check(in_array($d['kind'], $a->capabilities) && in_array((int) $d['service_id'], $a->service_ids), 'Service or capability not approved.');
            $service = PartnerService::where('partner_id', $p->partner_id)->where('is_active', true)->findOrFail($d['service_id']);
            $partner = CollaborationPartner::findOrFail($p->partner_id);
            $this->workflow->check(PartnerMembership::where('partner_id', $p->partner_id)->whereIn('role', ['intake', 'administrator_intake'])->where('is_active', true)->exists(), 'Partner needs an active intake member before receiving cases.');
            $w = app(SpecialistWorkflow::class);
            $request = null;
            $referral = null;
            if ($d['kind'] === 'diagnostic') {
                $request = $w->investigate($c->id, ['token' => $d['token'], 'destination' => 'external', 'name' => $service->name, 'clinical_diagnoses' => $d['summary'], 'external_destination' => $partner->name, 'partner_id' => $partner->id]);
                $request->update(['routing_method' => 'partner']);
            } else {
                $referral = $w->refer($c->id, ['token' => $d['token'], 'destination_type' => 'external', 'destination' => $partner->name, 'reason' => $d['purpose'], 'summary' => $d['summary'], 'urgency' => 'routine', 'partner_id' => $partner->id]);
                $referral->forceFill(['routing_method' => 'partner'])->save();
            }
            $case = PartnerCollaboration::create(['uuid' => (string) Str::uuid(), 'token' => $d['token'], 'partnership_id' => $p->id, 'service_id' => $service->id, 'consultation_id' => $c->id, 'investigation_request_id' => $request?->id, 'patient_referral_id' => $referral?->id, 'kind' => $d['kind'], 'created_by' => $u->id]);
            // Deliberately snapshot only the displayed identity and user-authored summary.
            PartnerDisclosure::create(['collaboration_id' => $case->id, 'agreement_id' => $a->id, 'packet' => ['patient_name' => $c->patient->name(), 'origin_reference' => $c->patient->hospital_number, 'summary' => $d['summary'], 'requested_service' => $service->name], 'purpose' => $d['purpose'], 'authorization_basis' => $d['authorization_basis'], 'expires_at' => min(Carbon::parse($d['expires_at']), $a->expires_at), 'created_by' => $u->id]);
            PartnerOutbox::create(['uuid' => (string) Str::uuid(), 'collaboration_id' => $case->id]);
            $this->workflow->audit('dispatched', $case, $u->id);

            return $case;
        }, 3);
    }

    public function respond(int $id, array $input): void
    {
        $d = Validator::make($input, ['action' => ['required', Rule::in(['accepted', 'declined', 'scheduled', 'in_progress', 'presented', 'admitted', 'discharged', 'cancelled', 'message'])], 'version' => ['required', 'integer'], 'token' => ['required', 'uuid'], 'message' => ['required', 'string', 'max:10000'], 'occurred_at' => ['required', 'date', 'before_or_equal:now'], 'remote_reference' => ['nullable', 'string', 'max:255']])->validate();
        DB::transaction(function () use ($id, $d) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $lockedPartnership = Partnership::lockForUpdate()->findOrFail($c->partnership_id);
            $u = $this->access->portalCase($c, ! in_array($d['action'], ['accepted', 'declined']), $d['action'] === 'message' ? 'case_messages' : 'update_status');
            if ($old = PartnerCaseEvent::where('token', $d['token'])->first()) {
                $this->workflow->check($old->collaboration_id === $c->id, 'Event token already used.');

                return;
            }
            $this->workflow->check($c->version === (int) $d['version'], 'Case changed; reload.');
            $map = ['requested' => ['accepted', 'declined'], 'accepted' => ['scheduled', 'in_progress', 'presented', 'cancelled'], 'scheduled' => ['in_progress', 'presented', 'cancelled'], 'in_progress' => ['admitted', 'discharged', 'cancelled'], 'presented' => ['admitted', 'in_progress', 'discharged', 'cancelled'], 'admitted' => ['discharged'], 'discharged' => []];
            $this->workflow->check($d['action'] === 'message' || in_array($d['action'], $map[$c->status] ?? []), 'Invalid case transition.');
            $this->workflow->check(! in_array($d['action'], ['presented', 'admitted', 'discharged']) || $c->kind === 'hospital', 'Hospital event is not applicable.');
            PartnerCaseEvent::create(['collaboration_id' => $id, 'token' => $d['token'], 'action' => $d['action'], 'message' => $d['message'], 'details' => ['remote_reference' => $d['remote_reference'] ?? null], 'user_id' => $u->id, 'occurred_at' => $d['occurred_at']]);
            if ($d['action'] !== 'message') {
                $c->status = $d['action'];
            }
            if ($d['action'] === 'accepted') {
                $c->assigned_user_id = $u->id;
                $c->accepted_at = now();
            }
            $c->version++;
            $c->save();
            $this->workflow->audit('case_response', $c, $u->id, ['action' => $d['action']]);
            if ($c->patient_referral_id && in_array($d['action'], ['accepted', 'declined'])) {
                PatientReferral::whereKey($c->patient_referral_id)->update(['status' => $d['action'] === 'accepted' ? 'Accepted' : 'Rejected', 'accepted_date' => $d['action'] === 'accepted' ? now() : null]);
            }
        }, 3);
    }

    public function submit(int $id, array $input): void
    {
        $d = Validator::make($input, ['token' => ['required', 'uuid'], 'content' => ['required', 'string', 'max:30000'], 'version' => ['required', 'integer']])->validate();
        DB::transaction(function () use ($id, $d) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $lockedPartnership = Partnership::lockForUpdate()->findOrFail($c->partnership_id);
            $u = $this->access->portalCase($c, true, 'submit_reports');
            if ($old = PartnerSubmission::where('token', $d['token'])->first()) {
                $this->workflow->check($old->collaboration_id === $c->id, 'Submission token already used.');

                return;
            }
            $this->workflow->check($c->version === (int) $d['version'] && in_array($c->status, ['accepted', 'in_progress', 'discharged', 'report_submitted', 'completed']), 'Case changed or does not accept reports.');
            $s = PartnerSubmission::create(['collaboration_id' => $id, 'token' => $d['token'], 'version' => (int) PartnerSubmission::where('collaboration_id', $id)->max('version') + 1, 'content' => $d['content'], 'submitted_by' => $u->id]);
            $c->update(['status' => 'report_submitted', 'completed_at' => null, 'version' => $c->version + 1]);
            if ($c->investigation_request_id) {
                InvestigationRequest::whereKey($c->investigation_request_id)->update(['completed_at' => now(), 'reviewed_at' => null, 'reviewed_by' => null]);
            }
            $this->workflow->audit('report_submitted', $s, $u->id);
        }, 3);
    }

    public function review(int $id, array $input): void
    {
        $u = $this->access->staff('partner_result.receive');
        $d = Validator::make($input, ['submission_id' => ['required', 'integer'], 'review' => ['required', 'string', 'max:10000']])->validate();
        DB::transaction(function () use ($id, $d, $u) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $this->access->clinical($c);
            app(SpecialistAccess::class)->consultation($c->consultation_id, 'specialist_result.review');
            $s = PartnerSubmission::where('collaboration_id', $id)->latest('version')->firstOrFail();
            $this->workflow->check($s->id === (int) $d['submission_id'] && ! $s->reviewed_at, 'Review the latest unreviewed submission.');
            $s->update(['reviewed_by' => $u->id, 'reviewed_at' => now(), 'review' => $d['review']]);
            $c->update(['status' => 'completed', 'completed_at' => now(), 'version' => $c->version + 1]);
            if ($c->investigation_request_id) {
                InvestigationRequest::whereKey($c->investigation_request_id)->update(['reviewed_by' => $u->id, 'reviewed_at' => now(), 'external_summary' => $d['review']]);
            }
            if ($c->patient_referral_id) {
                PatientReferral::whereKey($c->patient_referral_id)->update(['status' => 'Completed', 'completed_date' => now(), 'outcome' => $d['review']]);
            }
            $this->workflow->audit('report_reviewed', $s, $u->id);
        });
    }

    public function revoke(int $id): void
    {
        $u = $this->access->staff('partner_collaboration.manage');
        DB::transaction(function () use ($id, $u) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $this->access->clinical($c);
            PartnerDisclosure::where('collaboration_id', $id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $this->workflow->audit('disclosure_revoked', $c, $u->id);
        });
    }

    public function assign(int $id, array $input): void
    {
        $d = Validator::make($input, ['user_id' => ['required', 'integer'], 'version' => ['required', 'integer']])->validate();
        DB::transaction(function () use ($id, $d) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            Partnership::lockForUpdate()->findOrFail($c->partnership_id);
            $u = $this->access->portalCase($c, true);
            $this->workflow->check($c->version === (int) $d['version'] && ! in_array($c->status, ['completed', 'cancelled', 'declined']), 'Case changed or closed.');
            $p = Partnership::findOrFail($c->partnership_id);
            $member = PartnerMembership::where('partner_id', $p->partner_id)->where('user_id', $d['user_id'])->where('is_active', true)->whereIn('role', ['intake', 'worker', 'administrator_intake'])->firstOrFail();
            $c->update(['assigned_user_id' => $member->user_id, 'version' => $c->version + 1]);
            $this->workflow->audit('case_assigned', $c, $u->id, ['recipient_id' => $member->user_id]);
        });
    }

    public function disclose(int $id, array $input): void
    {
        $u = $this->access->staff('partner_collaboration.manage');
        $d = Validator::make($input, ['summary' => ['required', 'string', 'max:20000'], 'purpose' => ['required', 'string', 'max:2000'], 'authorization_basis' => ['required', 'string', 'max:5000'], 'expires_at' => ['required', 'date', 'after:now'], 'confirm_disclosure' => ['accepted']])->validate();
        DB::transaction(function () use ($id, $d, $u) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $this->access->clinical($c);
            $p = Partnership::lockForUpdate()->findOrFail($c->partnership_id);
            $a = $this->access->agreement($p);
            $this->workflow->check(in_array($c->kind, $a->capabilities) && in_array($c->service_id, $a->service_ids), 'Agreement no longer permits this case.');
            $this->workflow->check(! in_array($c->status, ['cancelled', 'declined']), 'This case is closed. Create a new authorized referral.');
            $old = PartnerDisclosure::where('collaboration_id', $id)->latest('id')->firstOrFail();
            $packet = array_replace($old->packet, ['summary' => $d['summary']]);
            PartnerDisclosure::create(['collaboration_id' => $id, 'agreement_id' => $a->id, 'packet' => $packet, 'purpose' => $d['purpose'], 'authorization_basis' => $d['authorization_basis'], 'expires_at' => min(Carbon::parse($d['expires_at']), $a->expires_at), 'created_by' => $u->id]);
            $this->workflow->audit('disclosure_revised', $c, $u->id);
        });
    }

    public function cancel(int $id, array $input): void
    {
        $u = $this->access->staff('partner_collaboration.manage');
        $d = Validator::make($input, ['reason' => ['required', 'string', 'max:5000']])->validate();
        DB::transaction(function () use ($id, $d, $u) {
            $c = PartnerCollaboration::lockForUpdate()->findOrFail($id);
            $this->access->clinical($c);
            $this->workflow->check(! in_array($c->status, ['completed', 'declined', 'cancelled']), 'Case is already closed.');
            $c->update(['status' => 'cancelled', 'version' => $c->version + 1]);
            PartnerDisclosure::where('collaboration_id', $id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            if ($c->patient_referral_id) {
                PatientReferral::whereKey($c->patient_referral_id)->update(['status' => 'Cancelled', 'outcome' => $d['reason']]);
            }
            if ($c->investigation_request_id) {
                InvestigationRequest::whereKey($c->investigation_request_id)->update(['status' => 'Cancelled']);
            }
            $this->workflow->audit('case_cancelled', $c, $u->id, ['reason' => $d['reason']]);
        });
    }
}
