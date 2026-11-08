<?php

namespace App\Services\PartnerNetwork;

use App\Models\AuditLog;
use App\Models\CollaborationPartner;
use App\Models\PartnerInvitation;
use App\Models\PartnerMembership;
use App\Models\PartnerOnboardingSubmission;
use App\Models\PartnerService;
use App\Models\Partnership;
use App\Models\PartnershipAgreement;
use App\Models\PartnerType;
use App\Models\PartnerVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Workflow
{
    public function __construct(public Access $access) {}

    public function check(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['network' => $message]);
        }
    }

    public function audit(string $action, $record, int $actor, array $meta = []): void
    {
        AuditLog::create(['actor_id' => $actor, 'action' => 'partner.'.$action, 'model_type' => get_class($record), 'model_id' => $record->id, 'meta' => $meta, 'ip' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public function register(array $input): Partnership
    {
        $u = $this->access->staff('partner.register');
        $d = Validator::make($input, ['name' => ['required', 'string', 'max:255'], 'type_id' => ['required', 'integer', 'exists:partner_types,id'], 'purpose' => ['required', 'string', 'max:2000'], 'existing_partner_id' => ['nullable', 'integer', 'exists:collaboration_partners,id'], 'contact' => ['required', 'string', 'max:255']])->validate();

        return DB::transaction(function () use ($u, $d) {
            $source = CollaborationPartner::where('is_local', true)->lockForUpdate()->firstOrFail();
            $type = PartnerType::where('is_active', true)->findOrFail($d['type_id']);
            if (empty($d['existing_partner_id'])) {
                $this->check(! CollaborationPartner::where('is_local', false)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($d['name']))])->exists(), 'A provider with this name is already registered. Reuse its directory entry or clarify the distinct practice name.');
            }
            $partner = isset($d['existing_partner_id']) ? CollaborationPartner::where('is_local', false)->findOrFail($d['existing_partner_id']) : CollaborationPartner::create(['uuid' => (string) Str::uuid(), 'name' => $d['name'], 'type' => $type->code, 'contact' => $d['contact'], 'is_active' => true]);
            $this->check(! Partnership::where('source_id', $source->id)->where('partner_id', $partner->id)->exists(), 'This provider already has a relationship. Open it instead.');
            $p = Partnership::create(['uuid' => (string) Str::uuid(), 'source_id' => $source->id, 'partner_id' => $partner->id, 'partner_type_id' => $type->id, 'purpose' => $d['purpose'], 'created_by' => $u->id]);
            $this->audit('registered', $p, $u->id);

            return $p;
        });
    }

    public function invite(int $id, array $input, bool $portal = false): string
    {
        $u = $portal ? $this->access->member(Partnership::findOrFail($id), true) : $this->access->staff('partner.invite');
        $d = Validator::make($input, ['email' => ['required', 'email', 'max:255'], 'role' => ['required', Rule::in(['administrator', 'intake', 'worker', 'administrator_intake'])]])->validate();

        return DB::transaction(function () use ($id, $u, $d, $portal) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            if ($portal) {
                $this->access->member($p, true);
            }
            $this->check(! in_array($p->status, ['terminated', 'rejected', 'suspended']), 'Invitations unavailable in this state.');
            PartnerInvitation::where('partnership_id', $id)->where('email', strtolower($d['email']))->where('status', 'pending')->update(['status' => 'revoked']);
            $secret = Str::random(64);
            $i = PartnerInvitation::create(['uuid' => (string) Str::uuid(), 'partnership_id' => $id, 'email' => strtolower($d['email']), 'role' => $d['role'], 'token_hash' => hash('sha256', $secret), 'expires_at' => now()->addDays(7), 'created_by' => $u->id]);
            if ($p->status === 'prospective') {
                $p->update(['status' => 'invited', 'version' => $p->version + 1]);
            }$this->audit('invited', $p, $u->id, ['invitation_id' => $i->id]);

            return $secret;
        });
    }

    public function redeem(string $token, array $input): User
    {
        $this->access->enabled();
        $d = Validator::make($input, ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email'], 'password' => ['required', 'string', 'max:200']])->validate();

        return DB::transaction(function () use ($token, $d) {
            $i = PartnerInvitation::where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            $this->check($i->status === 'pending' && $i->expires_at->gt(now()) && hash_equals($i->email, strtolower($d['email'])), 'Invitation unavailable or account does not match.');
            $p = Partnership::findOrFail($i->partnership_id);
            $this->check(! in_array($p->status, ['terminated', 'suspended', 'rejected']), 'Invitation unavailable.');
            $u = User::where('email', $i->email)->first();
            if ($u) {
                $this->check(Hash::check($d['password'], $u->password), 'Use the existing account password.');
            } else {
                $this->check(mb_strlen($d['password']) >= 12, 'New accounts need a password of at least 12 characters.');
                $u = new User(['name' => $d['name'], 'email' => $i->email, 'password' => $d['password']]);
                $u->is_partner_only = true;
            }
            $u->email_verified_at = $u->email_verified_at ?? now();
            $u->save();
            PartnerMembership::updateOrCreate(['partner_id' => $p->partner_id, 'user_id' => $u->id], ['role' => $i->role, 'is_active' => true]);
            $i->update(['status' => 'accepted', 'accepted_at' => now()]);
            $this->audit('invitation_accepted', $p, $u->id);

            return $u;
        });
    }

    public function submit(int $id, array $input): void
    {
        $d = Validator::make($input, ['contact_person' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:60'], 'email' => ['required', 'email'], 'address' => ['required', 'string', 'max:2000'], 'professional_information' => ['nullable', 'string', 'max:10000'], 'specialty' => ['nullable', 'string', 'max:255'], 'modes' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:10000']])->validate();
        DB::transaction(function () use ($id, $d) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            $u = $this->access->member($p, true);
            $this->check(in_array($p->status, ['invited', 'registration_submitted', 'prospective']), 'Registration is not open for changes.');
            $t = PartnerType::findOrFail($p->partner_type_id);
            $s = PartnerOnboardingSubmission::create(['partnership_id' => $id, 'version' => (int) PartnerOnboardingSubmission::where('partnership_id', $id)->max('version') + 1, 'details' => $d, 'requirements' => $t->requirements, 'template_version' => $t->version, 'submitted_by' => $u->id]);
            $p->update(['status' => 'registration_submitted', 'version' => $p->version + 1]);
            $this->audit('registration_submitted', $s, $u->id);
        });
    }

    public function transition(int $id, array $input): void
    {
        $d = Validator::make($input, ['status' => ['required', Rule::in(['under_review', 'registration_submitted', 'approved', 'rejected', 'onboarding', 'active', 'suspended', 'inactive', 'terminated'])], 'version' => ['required', 'integer'], 'reason' => ['required', 'string', 'max:5000'], 'checks' => ['nullable', 'array'], 'checks.*' => ['string', 'max:255']])->validate();
        $permission = match ($d['status']) {
            'approved','rejected' => 'partner.approve','suspended','inactive','terminated' => 'partner.suspend','active','onboarding' => 'partnership.manage',default => 'partner.verify'
        };
        $u = $this->access->staff($permission);
        DB::transaction(function () use ($id, $d, $u) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            $this->check($p->version === (int) $d['version'], 'Relationship changed; reload.');
            $map = ['registration_submitted' => ['under_review'], 'under_review' => ['registration_submitted', 'approved', 'rejected'], 'approved' => ['onboarding'], 'onboarding' => ['active'], 'active' => ['suspended', 'inactive', 'terminated'], 'suspended' => ['under_review', 'terminated'], 'inactive' => ['under_review', 'terminated'], 'rejected' => ['registration_submitted']];
            $this->check(in_array($d['status'], $map[$p->status] ?? []), 'Invalid relationship transition.');
            if ($d['status'] === 'approved') {
                $s = PartnerOnboardingSubmission::where('partnership_id', $id)->latest('version')->firstOrFail();
                $this->check($s->submitted_by !== $u->id && $p->created_by !== $u->id, 'Approval requires an independent reviewer.');
                $this->check(count(array_diff($s->requirements, $d['checks'] ?? [])) === 0, 'Confirm every pinned verification requirement.');
                PartnerVerification::create(['submission_id' => $s->id, 'checks' => $d['checks'] ?? [], 'reason' => $d['reason'], 'reviewed_by' => $u->id]);
                $p->approved_by = $u->id;
                $p->approved_at = now();
            }
            if ($d['status'] === 'active') {
                $a = PartnershipAgreement::where('partnership_id', $id)->latest('version')->firstOrFail();
                $this->check($a->accepted_at && $a->effective_at->lte(now()) && $a->expires_at->gt(now()) && count($a->service_ids) > 0 && count($a->capabilities) > 0, 'A current acknowledged agreement with approved services is required.');
            }
            $p->status = $d['status'];
            $p->version++;
            $p->save();
            $this->audit('state_changed', $p, $u->id, ['status' => $p->status, 'reason' => $d['reason']]);
        });
    }

    public function agreement(int $id, array $input): void
    {
        $u = $this->access->staff('partnership.manage');
        $d = Validator::make($input, ['reference' => ['required', 'string', 'max:255'], 'terms' => ['required', 'string', 'max:30000'], 'capabilities' => ['required', 'array', 'min:1'], 'capabilities.*' => [Rule::in(['diagnostic', 'hospital', 'specialist', 'submit_reports', 'exchange_documents', 'case_messages', 'update_status'])], 'service_ids' => ['required', 'array', 'min:1'], 'service_ids.*' => ['integer'], 'effective_at' => ['required', 'date'], 'expires_at' => ['required', 'date', 'after:effective_at']])->validate();
        DB::transaction(function () use ($id, $d, $u) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            $this->check(in_array($p->status, ['approved', 'onboarding', 'active']), 'Approve the relationship first.');
            $this->check(count(array_intersect($d['capabilities'], ['diagnostic', 'hospital', 'specialist'])) > 0, 'Approve at least one clinical request type.');
            $ids = array_values(array_unique(array_map('intval', $d['service_ids'])));
            $this->check(PartnerService::where('partner_id', $p->partner_id)->where('is_active', true)->whereIn('id', $ids)->count() === count($ids), 'Choose active services belonging to this partner.');
            $a = PartnershipAgreement::create(array_replace($d, ['partnership_id' => $id, 'version' => (int) PartnershipAgreement::where('partnership_id', $id)->max('version') + 1, 'service_ids' => $ids, 'created_by' => $u->id]));
            $p->update(['status' => 'onboarding', 'version' => $p->version + 1]);
            $this->audit('agreement_created', $a, $u->id);
        });
    }

    public function acknowledge(int $id): void
    {
        DB::transaction(function () use ($id) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            $u = $this->access->member($p, true);
            $this->check($p->status === 'onboarding', 'Agreement is not awaiting acknowledgement.');
            $a = PartnershipAgreement::where('partnership_id', $id)->latest('version')->firstOrFail();
            if (! $a->accepted_at) {
                $a->update(['accepted_by' => $u->id, 'accepted_at' => now()]);
                $this->audit('agreement_acknowledged', $a, $u->id);
            }
        });
    }
}
