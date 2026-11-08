<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_partner_only')->default(false));
        Schema::table('collaboration_partners', function (Blueprint $t) {
            $t->uuid('uuid')->nullable()->unique();
            $t->boolean('is_local')->default(false);
            $t->json('details')->nullable();
        });
        DB::table('collaboration_partners')->orderBy('id')->each(function ($p) {
            DB::table('collaboration_partners')->where('id', $p->id)->update(['uuid' => (string) Str::uuid()]);
        });
        Schema::create('partner_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->json('requirements');
            $t->unsignedInteger('version')->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('partnerships', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('source_id')->constrained('collaboration_partners');
            $t->foreignId('partner_id')->constrained('collaboration_partners');
            $t->foreignId('partner_type_id')->constrained();
            $t->string('status')->default('prospective')->index();
            $t->text('purpose');
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['source_id', 'partner_id']);
        });
        Schema::create('partner_memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained('collaboration_partners');
            $t->foreignId('user_id')->constrained();
            $t->string('role')->default('administrator');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['partner_id', 'user_id']);
        });
        Schema::create('partner_invitations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('partnership_id')->constrained();
            $t->string('email');
            $t->string('role');
            $t->string('token_hash', 64)->unique();
            $t->string('status')->default('pending');
            $t->timestamp('expires_at');
            $t->timestamp('accepted_at')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('partner_onboarding_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partnership_id')->constrained();
            $t->unsignedInteger('version');
            $t->json('details');
            $t->json('requirements');
            $t->unsignedInteger('template_version');
            $t->foreignId('submitted_by')->constrained('users');
            $t->timestamps();
            $t->unique(['partnership_id', 'version']);
        });
        Schema::create('partner_verifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('partner_onboarding_submissions');
            $t->json('checks');
            $t->text('reason');
            $t->foreignId('reviewed_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('partner_services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partner_id')->constrained('collaboration_partners');
            $t->string('name');
            $t->string('specialty')->nullable();
            $t->string('location');
            $t->string('availability')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('partnership_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partnership_id')->constrained();
            $t->unsignedInteger('version');
            $t->string('reference');
            $t->text('terms');
            $t->json('capabilities');
            $t->json('service_ids');
            $t->timestamp('effective_at');
            $t->timestamp('expires_at');
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('accepted_by')->nullable()->constrained('users');
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
            $t->unique(['partnership_id', 'version']);
        });
        Schema::create('partner_collaborations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->uuid('token')->unique();
            $t->foreignId('partnership_id')->constrained();
            $t->foreignId('service_id')->constrained('partner_services');
            $t->foreignId('consultation_id')->constrained('specialist_consultations');
            $t->foreignId('investigation_request_id')->nullable()->unique()->constrained();
            $t->foreignId('patient_referral_id')->nullable()->unique()->constrained();
            $t->string('kind');
            $t->string('status')->default('requested')->index();
            $t->unsignedInteger('version')->default(1);
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('assigned_user_id')->nullable()->constrained('users');
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE partner_collaborations ADD CONSTRAINT partner_case_one_order CHECK ((investigation_request_id IS NULL) <> (patient_referral_id IS NULL))');
        }
        Schema::create('partner_disclosures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('collaboration_id')->constrained('partner_collaborations');
            $t->foreignId('agreement_id')->constrained('partnership_agreements');
            $t->json('packet');
            $t->text('purpose');
            $t->text('authorization_basis');
            $t->timestamp('expires_at');
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('partner_case_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('collaboration_id')->constrained('partner_collaborations');
            $t->uuid('token')->unique();
            $t->string('action');
            $t->text('message');
            $t->json('details')->nullable();
            $t->foreignId('user_id')->constrained();
            $t->timestamp('occurred_at');
            $t->timestamps();
        });
        Schema::create('partner_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('collaboration_id')->constrained('partner_collaborations');
            $t->uuid('token')->unique();
            $t->unsignedInteger('version');
            $t->text('content');
            $t->foreignId('submitted_by')->constrained('users');
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review')->nullable();
            $t->timestamps();
            $t->unique(['collaboration_id', 'version']);
        });
        Schema::create('partner_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('partnership_id')->constrained();
            $t->foreignId('collaboration_id')->nullable()->constrained('partner_collaborations');
            $t->string('name');
            $t->string('path');
            $t->string('mime');
            $t->unsignedBigInteger('size');
            $t->string('sha256', 64);
            $t->string('status')->default('quarantined');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->foreignId('released_by')->nullable()->constrained('users');
            $t->text('release_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('partner_outbox', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('collaboration_id')->constrained('partner_collaborations');
            $t->string('status')->default('pending')->index();
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('next_attempt_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
        foreach (['patient_referrals', 'investigation_requests'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('routing_method')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['patient_referrals', 'investigation_requests'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('routing_method'));
        }
        foreach (['partner_outbox', 'partner_documents', 'partner_submissions', 'partner_case_events', 'partner_disclosures', 'partner_collaborations', 'partnership_agreements', 'partner_services', 'partner_verifications', 'partner_onboarding_submissions', 'partner_invitations', 'partner_memberships', 'partnerships', 'partner_types'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('collaboration_partners', fn (Blueprint $t) => $t->dropColumn(['uuid', 'is_local', 'details']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_partner_only'));
    }
};
