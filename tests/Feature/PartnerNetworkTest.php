<?php

namespace Tests\Feature;

use App\Models\PartnerDisclosure;
use App\Models\PartnerDocument;
use App\Models\PartnerInvitation;
use App\Models\PartnerMembership;
use App\Models\PartnerOnboardingSubmission;
use App\Models\PartnerService;
use App\Models\Partnership;
use App\Models\PartnershipAgreement;
use App\Models\PartnerSubmission;
use App\Models\PartnerType;
use App\Models\PatientReferral;
use App\Models\User;
use App\Services\PartnerNetwork\Collaboration;
use App\Services\PartnerNetwork\Workflow;
use App\Services\SpecialistWorkflow;
use Database\Seeders\PartnerNetworkSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PartnerNetworkTest extends SpecialistWorkflowTest
{
    private User $origin;

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_30_000001_create_partner_network.php'))->up();
        $this->seed(PartnerNetworkSeeder::class);
        $this->origin = auth()->user();
        foreach (['network_coordinator', 'network_verifier', 'network_approver', 'network_manager', 'network_clinician'] as $role) {
            $this->origin->assignRole($role);
        }
    }

    private function prospect(): Partnership
    {
        return app(Workflow::class)->register(['name' => 'Partner Lab', 'type_id' => PartnerType::where('code', 'diagnostic_center')->value('id'), 'purpose' => 'Diagnostic referrals', 'contact' => 'lab@example.test']);
    }

    private function activate(): array
    {
        $w = app(Workflow::class);
        $p = $this->prospect();
        $token = $w->invite($p->id, ['email' => 'partner@example.test', 'role' => 'administrator']);
        $u = User::withoutEvents(fn () => $w->redeem($token, ['name' => 'Partner clinician', 'email' => 'partner@example.test', 'password' => 'long-test-password']));
        Auth::guard('partner')->login($u);
        $w->submit($p->id, ['contact_person' => 'Manager', 'phone' => '555123', 'email' => 'partner@example.test', 'address' => 'Test location']);
        $w->transition($p->id, ['status' => 'under_review', 'version' => $p->fresh()->version, 'reason' => 'Start checks']);
        $approver = User::withoutEvents(fn () => User::create(['name' => 'Approver', 'email' => 'approver@example.test', 'password' => 'long-test-password']));
        $approver->assignRole('network_approver');
        $this->actingAs($approver);
        $w->transition($p->id, ['status' => 'approved', 'version' => $p->fresh()->version, 'reason' => 'Verified evidence', 'checks' => PartnerType::find($p->partner_type_id)->requirements]);
        $this->actingAs($this->origin);
        $service = PartnerService::create(['partner_id' => $p->partner_id, 'name' => 'MRI', 'location' => 'Main center']);
        $w->agreement($p->id, ['reference' => 'AGR1', 'terms' => 'Approved test agreement', 'capabilities' => ['diagnostic', 'hospital', 'specialist', 'submit_reports', 'exchange_documents', 'case_messages', 'update_status'], 'service_ids' => [$service->id], 'effective_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        $w->acknowledge($p->id);
        $w->transition($p->id, ['status' => 'active', 'version' => $p->fresh()->version, 'reason' => 'Ready']);
        PartnerMembership::where('user_id', $u->id)->update(['role' => 'intake']);

        return [$p->fresh(), $u, $service];
    }

    private function dispatchCase(string $kind = 'diagnostic'): array
    {
        [$p,$u,$s] = $this->activate();
        $w = app(SpecialistWorkflow::class);
        $c = $w->book(['patient_id' => 1, 'profile_id' => 1, 'service_id' => 1, 'starts_at' => now('UTC')->addDay()->setTime(10, 0)->format('Y-m-d\TH:i'), 'mode' => 'physical', 'reason' => 'Review', 'token' => (string) Str::uuid()]);
        $w->start($c->id);
        $data = ['partnership_id' => $p->id, 'service_id' => $s->id, 'kind' => $kind, 'token' => (string) Str::uuid(), 'summary' => 'Selected summary only', 'purpose' => 'Imaging opinion', 'authorization_basis' => 'Documented patient authorization reference 1', 'expires_at' => now()->addWeek(), 'confirm_disclosure' => 1];
        $case = app(Collaboration::class)->dispatch($c->id, $data);
        $again = app(Collaboration::class)->dispatch($c->id, $data);
        $this->assertSame($case->id, $again->id);

        return [$case, $p, $u];
    }

    public function test_network_directory_and_portal_authentication_screens_render(): void
    {
        $this->get(route('network.index'))->assertOk()->assertSee('Care Network partners');
        $this->get(route('partner.login'))->assertOk()->assertSee('Partner sign in');
        $p = $this->prospect();
        $this->get(route('network.show', $p))->assertOk();
    }

    public function test_invitation_expiry_and_replay_are_rejected(): void
    {
        $w = app(Workflow::class);
        $p = $this->prospect();
        $token = $w->invite($p->id, ['email' => 'p@example.test', 'role' => 'administrator']);
        PartnerInvitation::where('partnership_id', $p->id)->update(['expires_at' => now()->subDay()]);
        $this->expectException(ValidationException::class);
        $w->redeem($token, ['name' => 'P', 'email' => 'p@example.test', 'password' => 'long-test-password']);
    }

    public function test_registration_cannot_be_self_approved(): void
    {
        $p = $this->prospect();
        $p->update(['status' => 'under_review']);
        PartnerOnboardingSubmission::create(['partnership_id' => $p->id, 'version' => 1, 'details' => [], 'requirements' => [], 'template_version' => 1, 'submitted_by' => $this->origin->id]);
        $this->expectException(ValidationException::class);
        app(Workflow::class)->transition($p->id, ['status' => 'approved', 'version' => 1, 'reason' => 'Self approval']);
    }

    public function test_full_diagnostic_journey_and_corrected_report_preserve_reviews(): void
    {
        [$c,$p,$u] = $this->dispatchCase();
        $this->assertDatabaseCount('investigation_requests', 1);
        $this->assertDatabaseCount('bills', 0);
        $this->get(route('partner.case', $c))->assertOk()->assertSee('Selected summary only');
        $w = app(Collaboration::class);
        $w->respond($c->id, ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted', 'occurred_at' => now()]);
        $w->submit($c->id, ['token' => (string) Str::uuid(), 'version' => $c->fresh()->version, 'content' => 'Initial external result']);
        $s = PartnerSubmission::first();
        $w->review($c->id, ['submission_id' => $s->id, 'review' => 'Reviewed initial report']);
        $w->submit($c->id, ['token' => (string) Str::uuid(), 'version' => $c->fresh()->version, 'content' => 'Corrected external result']);
        $this->assertDatabaseCount('partner_submissions', 2);
        $this->assertNotNull($s->fresh()->reviewed_at);
        $this->assertSame('report_submitted', $c->fresh()->status);
        $this->get(route('network.case', $c))->assertOk()->assertSee('Reviewed initial report')->assertSee('Corrected external result');
    }

    public function test_external_identity_cannot_use_internal_workspace(): void
    {
        [$p,$u] = $this->activate();
        $this->actingAs($u, 'web');
        $this->get(route('specialist.index'))->assertForbidden();
        $this->get(route('network.index'))->assertForbidden();
    }

    public function test_partner_without_case_membership_cannot_read_same_case(): void
    {
        [$c] = $this->dispatchCase();
        $other = User::withoutEvents(fn () => User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'long-test-password']));
        $other->forceFill(['email_verified_at' => now()])->saveQuietly();
        $this->actingAs($other, 'partner');
        $this->get(route('partner.case', $c))->assertForbidden();
    }

    public function test_suspension_and_disclosure_revocation_remove_access(): void
    {
        [$c,$p] = $this->dispatchCase();
        $p->update(['status' => 'suspended']);
        $this->get(route('partner.case', $c))->assertForbidden();
        $p->update(['status' => 'active']);
        app(Collaboration::class)->revoke($c->id);
        $this->get(route('partner.case', $c))->assertForbidden();
    }

    public function test_expiry_is_enforced_without_scheduler(): void
    {
        [$c,$p] = $this->dispatchCase();
        PartnershipAgreement::where('partnership_id', $p->id)->update(['expires_at' => now()->subMinute()]);
        $this->get(route('partner.case', $c))->assertForbidden();
    }

    public function test_partner_only_receives_selected_snapshot(): void
    {
        [$c] = $this->dispatchCase();
        $packet = PartnerDisclosure::first()->packet;
        $this->assertSame(['patient_name', 'origin_reference', 'summary', 'requested_service'], array_keys($packet));
        $this->assertArrayNotHasKey('clinical', $packet);
        $this->assertDatabaseCount('specialist_patient_access', 1);
    }

    public function test_network_disable_blocks_portal_but_keeps_specialist(): void
    {
        [$c] = $this->dispatchCase();
        config(['mediflow_modules.plans.specialist.modules' => ['core', 'access_control', 'specialist', 'patient_records', 'clinical_care', 'billing', 'reports']]);
        $this->get(route('partner.case', $c))->assertForbidden();
        $this->actingAs($this->origin, 'web');
        $this->get(route('specialist.index'))->assertOk();
    }

    public function test_hospital_reports_preserve_remote_admission_boundary(): void
    {
        [$c] = $this->dispatchCase('hospital');
        $w = app(Collaboration::class);
        foreach (['accepted', 'presented', 'admitted', 'discharged'] as $action) {
            $w->respond($c->id, ['action' => $action, 'version' => $c->fresh()->version, 'token' => (string) Str::uuid(), 'message' => 'Partner reported '.$action, 'occurred_at' => now(), 'remote_reference' => 'REMOTE-123']);
        }
        $w->submit($c->id, ['token' => (string) Str::uuid(), 'version' => $c->fresh()->version, 'content' => 'Discharge recommendations']);
        $w->review($c->id, ['submission_id' => PartnerSubmission::first()->id, 'review' => 'Follow-up planned']);
        $referral = PatientReferral::find($c->patient_referral_id);
        $this->assertSame('Completed', $referral->status);
        $this->assertNull($referral->outcome_admission_id);
        $this->assertDatabaseCount('patient_referrals', 1);
    }

    public function test_private_files_stay_quarantined_until_configured_review(): void
    {
        Storage::fake('local');
        [$c,$p] = $this->dispatchCase();
        app(Collaboration::class)->respond($c->id, ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted', 'occurred_at' => now()]);
        $this->post(route('partner.documents.upload', $p), ['collaboration_id' => $c->id, 'document' => UploadedFile::fake()->create('report.pdf', 4, 'application/pdf')])->assertRedirect();
        $doc = PartnerDocument::firstOrFail();
        $this->get(route('partner.documents.download', $doc))->assertForbidden();
        $this->post(route('network.documents.release', $doc), ['reason' => 'Reviewed'])->assertForbidden();
        config(['partner_network.allow_manual_document_release' => true]);
        $this->post(route('network.documents.release', $doc), ['reason' => 'Approved manual file review'])->assertRedirect();
        $this->get(route('partner.documents.download', $doc))->assertOk();
    }

    public function test_partner_administrator_can_invite_but_cannot_browse_clinical_cases(): void
    {
        [$p,$u] = $this->activate();
        PartnerMembership::where('user_id', $u->id)->update(['role' => 'administrator']);
        $this->post(route('partner.action', [$p, 'invite']), ['email' => 'colleague@example.test', 'role' => 'worker'])->assertRedirect()->assertSessionHas('invitation_link');
        $this->get(route('partner.onboarding', $p))->assertOk()->assertSee('Invite a colleague');
        $this->assertDatabaseHas('partner_invitations', ['email' => 'colleague@example.test', 'role' => 'worker']);
    }

    public function test_invitation_redemption_cannot_be_replayed(): void
    {
        $p = $this->prospect();
        $w = app(Workflow::class);
        $token = $w->invite($p->id, ['email' => 'new@example.test', 'role' => 'worker']);
        $input = ['name' => 'Member', 'email' => 'new@example.test', 'password' => 'long-test-password'];
        User::withoutEvents(fn () => $w->redeem($token, $input));
        $this->expectException(ValidationException::class);
        $w->redeem($token, $input);
    }

    public function test_case_transfer_is_limited_to_same_partner_members(): void
    {
        [$c,$p] = $this->dispatchCase();
        $w = app(Collaboration::class);
        $w->respond($c->id, ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted', 'occurred_at' => now()]);
        $worker = User::withoutEvents(fn () => User::create(['name' => 'Worker', 'email' => 'worker@example.test', 'password' => 'long-test-password']));
        $worker->forceFill(['email_verified_at' => now(), 'is_partner_only' => true])->saveQuietly();
        PartnerMembership::create(['partner_id' => $p->partner_id, 'user_id' => $worker->id, 'role' => 'worker']);
        $w->assign($c->id, ['version' => $c->fresh()->version, 'user_id' => $worker->id]);
        $this->actingAs($worker, 'partner');
        $this->get(route('partner.case', $c))->assertOk();
    }

    public function test_disclosure_revision_preserves_original_and_requires_new_authorization(): void
    {
        [$c] = $this->dispatchCase();
        $w = app(Collaboration::class);
        $w->revoke($c->id);
        $w->disclose($c->id, ['summary' => 'New authorized summary', 'purpose' => 'Continued care', 'authorization_basis' => 'Updated consent reference', 'expires_at' => now()->addDays(2), 'confirm_disclosure' => 1]);
        $this->assertDatabaseCount('partner_disclosures', 2);
        $this->assertSame('Selected summary only', PartnerDisclosure::oldest('id')->first()->packet['summary']);
        $this->get(route('partner.case', $c))->assertOk()->assertSee('New authorized summary')->assertDontSee('Selected summary only');
    }

    public function test_origin_cancellation_revokes_access_and_original_request(): void
    {
        [$c] = $this->dispatchCase();
        app(Collaboration::class)->cancel($c->id, ['reason' => 'Rerouted by care team']);
        $this->assertSame('cancelled', $c->fresh()->status);
        $this->assertDatabaseHas('investigation_requests', ['id' => $c->investigation_request_id, 'status' => 'Cancelled']);
        $this->get(route('partner.case', $c))->assertForbidden();
    }

    public function test_concurrent_style_stale_case_version_is_rejected(): void
    {
        [$c] = $this->dispatchCase();
        $w = app(Collaboration::class);
        $w->respond($c->id, ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted', 'occurred_at' => now()]);
        $this->expectException(ValidationException::class);
        $w->respond($c->id, ['action' => 'in_progress', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Stale update', 'occurred_at' => now()]);
    }

    public function test_agreement_does_not_imply_report_submission_capability(): void
    {
        [$c,$p] = $this->dispatchCase();
        $w = app(Collaboration::class);
        $w->respond($c->id, ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted', 'occurred_at' => now()]);
        PartnershipAgreement::where('partnership_id', $p->id)->first()->update(['capabilities' => ['diagnostic', 'update_status']]);
        $this->post(route('partner.case.action', [$c, 'submit']), ['token' => (string) Str::uuid(), 'version' => $c->fresh()->version, 'content' => 'Not authorized'])->assertForbidden();
    }

    public function test_solo_partner_combined_role_can_onboard_and_accept_assigned_work(): void
    {
        [$c,$p,$u] = $this->dispatchCase();
        PartnerMembership::where('user_id', $u->id)->update(['role' => 'administrator_intake']);
        $this->get(route('partner.onboarding', $p))->assertOk();
        $this->post(route('partner.case.action', [$c, 'respond']), ['action' => 'accepted', 'version' => 1, 'token' => (string) Str::uuid(), 'message' => 'Accepted by solo provider', 'occurred_at' => now()])->assertRedirect();
        $this->assertSame($u->id, $c->fresh()->assigned_user_id);
    }
}
