<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('specialist_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('title');
            $t->string('registration')->nullable();
            $t->string('subspecialty')->nullable();
            $t->string('timezone')->default('UTC');
            $t->string('location')->nullable();
            $t->boolean('online')->default(false);
            $t->boolean('physical')->default(true);
            $t->boolean('is_active')->default(true);
            $t->json('availability')->nullable();
            $t->timestamps();
        });
        Schema::create('specialist_profile_specialty', function (Blueprint $t) {
            $t->foreignId('specialist_profile_id')->constrained();
            $t->foreignId('specialty_id')->constrained();
            $t->primary(['specialist_profile_id', 'specialty_id']);
        });
        Schema::create('specialist_services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_id')->unique()->constrained();
            $t->foreignId('specialty_id')->constrained();
            $t->unsignedSmallInteger('duration_minutes')->default(30);
            $t->timestamps();
        });
        Schema::create('specialist_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('allow_walkins')->default(true);
            $t->string('operating_mode')->default('hybrid');
            $t->timestamps();
        });
        Schema::create('collaboration_partners', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type');
            $t->string('contact')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('specialist_patient_access', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('granted_by')->constrained('users');
            $t->timestamps();
            $t->unique(['patient_id', 'user_id']);
        });
        Schema::table('patient_visits', fn (Blueprint $t) => $t->boolean('specialist_origin')->default(false));
        Schema::table('appointments', function (Blueprint $t) {
            $t->foreignId('specialist_profile_id')->nullable()->constrained();
            $t->dateTime('starts_at')->nullable()->index();
            $t->dateTime('ends_at')->nullable();
            $t->string('timezone')->nullable();
        });
        Schema::create('specialist_consultations', function (Blueprint $t) {
            $t->id();
            $t->uuid('booking_token')->unique();
            $t->foreignId('patient_id')->constrained();
            $t->foreignId('patient_visit_id')->nullable()->constrained();
            $t->foreignId('appointment_id')->unique()->constrained();
            $t->foreignId('specialist_profile_id')->constrained();
            $t->foreignId('specialty_id')->constrained();
            $t->foreignId('service_id')->constrained();
            $t->string('service_name');
            $t->string('specialist_name');
            $t->string('specialty_name');
            $t->decimal('charge', 12, 2);
            $t->string('mode');
            $t->string('location')->nullable();
            $t->string('status')->default('scheduled')->index();
            $t->json('clinical')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->dateTime('started_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->foreignId('completed_by')->nullable()->constrained('users');
            $t->string('completed_name')->nullable();
            $t->date('followup_due')->nullable()->index();
            $t->foreignId('followup_consultation_id')->nullable()->constrained('specialist_consultations');
            $t->foreignId('bill_id')->nullable()->unique()->constrained();
            $t->timestamps();
        });
        Schema::create('specialist_amendments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('specialist_consultation_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('author_name');
            $t->text('reason');
            $t->text('content');
            $t->timestamps();
        });
        Schema::create('specialist_care_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('specialist_consultation_id')->constrained();
            $t->unsignedInteger('version');
            $t->text('problem');
            $t->text('objectives');
            $t->text('instructions');
            $t->date('review_date');
            $t->string('status');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->unique(['specialist_consultation_id', 'version']);
        });
        Schema::table('bills', fn (Blueprint $t) => $t->foreignId('specialist_consultation_id')->nullable()->constrained());
        Schema::table('prescriptions', function (Blueprint $t) {
            $t->foreignId('specialist_consultation_id')->nullable()->constrained();
            $t->uuid('specialist_token')->nullable()->unique();
            $t->string('fulfillment')->nullable();
        });
        Schema::table('prescription_items', fn (Blueprint $t) => $t->string('medicine_name')->nullable());
        Schema::table('investigation_requests', function (Blueprint $t) {
            $t->foreignId('specialist_consultation_id')->nullable()->constrained();
            $t->uuid('specialist_token')->nullable()->unique();
            $t->boolean('is_external')->default(false)->index();
            $t->string('requested_name')->nullable();
            $t->foreignId('collaboration_partner_id')->nullable()->constrained();
            $t->string('external_destination')->nullable();
            $t->text('external_summary')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
        });
        Schema::table('patient_referrals', function (Blueprint $t) {
            $t->foreignId('specialist_consultation_id')->nullable()->constrained();
            $t->uuid('specialist_token')->nullable()->unique();
            $t->string('destination_type')->nullable();
            $t->foreignId('destination_user_id')->nullable()->constrained('users');
            $t->foreignId('collaboration_partner_id')->nullable()->constrained();
            $t->string('urgency')->default('routine');
            $t->text('outcome')->nullable();
            $t->unsignedBigInteger('outcome_admission_id')->nullable();
        });
        Schema::create('specialist_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('specialist_consultation_id')->constrained();
            $t->foreignId('investigation_request_id')->nullable()->constrained();
            $t->foreignId('uploaded_by')->constrained('users');
            $t->string('path');
            $t->string('name');
            $t->string('mime');
            $t->unsignedBigInteger('size');
            $t->string('source');
            $t->date('report_date');
            $t->timestamps();
        });
        Schema::table('payments', fn (Blueprint $t) => $t->uuid('specialist_token')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('specialist_token'));
        Schema::dropIfExists('specialist_documents');
        Schema::table('patient_referrals', function (Blueprint $t) {
            $t->dropConstrainedForeignId('specialist_consultation_id');
            $t->dropConstrainedForeignId('destination_user_id');
            $t->dropConstrainedForeignId('collaboration_partner_id');
            $t->dropColumn(['specialist_token', 'destination_type', 'urgency', 'outcome', 'outcome_admission_id']);
        });
        Schema::table('investigation_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('specialist_consultation_id');
            $t->dropConstrainedForeignId('collaboration_partner_id');
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['specialist_token', 'is_external', 'requested_name', 'external_destination', 'external_summary', 'reviewed_at']);
        });
        Schema::table('prescription_items', fn (Blueprint $t) => $t->dropColumn('medicine_name'));
        Schema::table('prescriptions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('specialist_consultation_id');
            $t->dropColumn(['specialist_token', 'fulfillment']);
        });
        Schema::table('bills', fn (Blueprint $t) => $t->dropConstrainedForeignId('specialist_consultation_id'));
        Schema::table('patient_visits', fn (Blueprint $t) => $t->dropColumn('specialist_origin'));
        Schema::dropIfExists('specialist_care_plans');
        Schema::dropIfExists('specialist_amendments');
        Schema::dropIfExists('specialist_consultations');
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('specialist_profile_id');
            $t->dropColumn(['starts_at', 'ends_at', 'timezone']);
        });
        foreach (['specialist_patient_access', 'collaboration_partners', 'specialist_settings', 'specialist_services', 'specialist_profile_specialty', 'specialist_profiles', 'specialties'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
