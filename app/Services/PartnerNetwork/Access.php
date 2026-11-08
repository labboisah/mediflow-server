<?php

namespace App\Services\PartnerNetwork;

use App\Models\CollaborationPartner;
use App\Models\PartnerCollaboration;
use App\Models\PartnerDisclosure;
use App\Models\PartnerMembership;
use App\Models\Partnership;
use App\Models\PartnershipAgreement;
use App\Services\LicenseService;
use App\Services\PackageCapabilities;
use App\Services\SpecialistAccess;
use Illuminate\Support\Facades\Auth;

class Access
{
    public function enabled(): void
    {
        abort_unless(app(LicenseService::class)->moduleEnabled('partner_network') && app(PackageCapabilities::class)->available('specialist'), 403);
    }

    public function staff(string $permission)
    {
        $this->enabled();
        $u = Auth::guard('web')->user();
        abort_unless($u && ! $u->is_partner_only && $u->hasPermission($permission) && app(LicenseService::class)->userHasModuleAccess($u, 'partner_network'), 403);
        Auth::shouldUse('web');

        return $u;
    }

    public function member(Partnership $p, bool $admin = false)
    {
        $this->enabled();
        $u = Auth::guard('partner')->user();
        abort_unless($u && $u->email_verified_at, 403);
        $m = PartnerMembership::where('partner_id', $p->partner_id)->where('user_id', $u->id)->where('is_active', true)->first();
        abort_unless($m && (! $admin || in_array($m->role, ['administrator', 'administrator_intake'])), 403);

        return $u;
    }

    public function agreement(Partnership $p): PartnershipAgreement
    {
        abort_unless($p->status === 'active' && CollaborationPartner::find($p->partner_id)?->is_active, 403, 'Partnership is not active.');
        $a = PartnershipAgreement::where('partnership_id', $p->id)->latest('version')->first();
        abort_unless($a && $a->accepted_at && $a->effective_at->lte(now()) && $a->expires_at->gt(now()), 403, 'A current acknowledged agreement is required.');

        return $a;
    }

    public function clinical(PartnerCollaboration $c): void
    {
        $this->staff('partner_collaboration.read');
        app(SpecialistAccess::class)->consultation($c->consultation_id, 'specialist_consultation.read');
    }

    public function portalCase(PartnerCollaboration $c, bool $assigned = false, ?string $capability = null)
    {
        $p = Partnership::findOrFail($c->partnership_id);
        $u = $this->member($p);
        $a = $this->agreement($p);
        abort_unless($capability === null || in_array($capability, $a->capabilities), 403, 'This collaboration capability is not approved.');
        $m = PartnerMembership::where('partner_id', $p->partner_id)->where('user_id', $u->id)->firstOrFail();
        abort_unless(in_array($c->kind, $a->capabilities) && in_array($c->service_id, $a->service_ids), 403);
        $d = PartnerDisclosure::where('collaboration_id', $c->id)->latest('id')->firstOrFail();
        abort_unless(! $d->revoked_at && $d->expires_at->gt(now()), 403, 'Disclosure expired or revoked.');
        abort_unless($c->assigned_user_id === $u->id || (! $assigned && $c->status === 'requested' && in_array($m->role, ['intake', 'administrator_intake'])), 403);

        return $u;
    }
}
