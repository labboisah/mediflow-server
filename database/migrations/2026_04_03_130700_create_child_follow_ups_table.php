<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('child_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newborn_id')->constrained('newborns')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // mother
            $table->foreignId('recorded_by')->constrained('users');
            
            // Follow-up timing
            $table->dateTime('follow_up_date_time');
            $table->integer('days_of_life')->nullable();
            $table->enum('follow_up_period', ['day_3', 'day_7', 'day_10', 'day_14', '6weeks', '3months', '6months', 'year1'])->nullable();
            
            // Location of follow-up
            $table->enum('location', ['hospital', 'clinic', 'home', 'other'])->default('hospital');
            $table->text('location_details')->nullable();
            
            // General information
            $table->text('feeding_type')->nullable(); // breast, bottle, mixed
            $table->text('how_baby_is_feeding')->nullable();
            $table->text('mother_observations')->nullable();
            
            // Vital signs
            $table->string('temperature')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('respiratory_rate')->nullable();
            
            // Growth parameters
            $table->string('weight')->nullable();
            $table->string('length')->nullable();
            $table->string('head_circumference')->nullable();
            $table->decimal('weight_percentile', 5, 2)->nullable();
            
            // Weight gain assessment
            $table->string('weight_change_since_birth')->nullable();
            $table->string('weight_gain_rate')->nullable();
            $table->text('weight_assessment')->nullable();
            
            // General physical examination
            $table->text('general_appearance')->nullable();
            $table->text('activity_level')->nullable();
            $table->text('alertness')->nullable();
            $table->text('skin_examination')->nullable();
            
            // Cord care assessment (if applicable)
            $table->text('umbilical_cord_status')->nullable();
            $table->text('umbilical_discharge')->nullable();
            $table->text('signs_of_infection')->nullable();
            
            // Jaundice assessment
            $table->string('jaundice_present')->nullable();
            $table->text('jaundice_level')->nullable();
            $table->text('jaundice_management')->nullable();
            
            // Feeding assessment
            $table->text('breast_examination')->nullable();
            $table->text('latching_quality')->nullable();
            $table->text('suckling_pattern')->nullable();
            $table->text('milk_transfer')->nullable();
            $table->text('bottle_feeding_if_applicable')->nullable();
            $table->text('feeding_frequency')->nullable();
            $table->text('feeding_duration')->nullable();
            $table->text('feeding_problems')->nullable();
            $table->text('mother_nipple_problems')->nullable();
            
            // Elimination assessment
            $table->text('urinary_output')->nullable();
            $table->text('stool_output')->nullable();
            $table->text('stool_characteristics')->nullable();
            $table->text('elimination_problems')->nullable();
            
            // Neurological assessment
            $table->text('responsiveness')->nullable();
            $table->text('cry_quality')->nullable();
            $table->text('reflex_assessment')->nullable();
            $table->text('muscle_tone')->nullable();
            
            // Immunizations
            $table->boolean('immunizations_up_to_date')->default(false);
            $table->text('immunizations_given')->nullable();
            $table->text('immunizations_planned')->nullable();
            
            // Health screening (metabolic screening, hearing, etc.)
            $table->boolean('newborn_screening_done')->default(false);
            $table->text('newborn_screening_results')->nullable();
            $table->boolean('hearing_screening_done')->default(false);
            $table->text('hearing_screening_results')->nullable();
            
            // Developmental assessment
            $table->text('developmental_milestones')->nullable();
            $table->text('developmental_concerns')->nullable();
            
            // Mother's health status
            $table->text('mother_recovery_status')->nullable();
            $table->text('mother_emotional_wellbeing')->nullable();
            $table->text('mother_breastfeeding_support')->nullable();
            
            // Complications or concerns identified
            $table->text('baby_concerns')->nullable();
            $table->text('mother_concerns')->nullable();
            $table->text('complications_identified')->nullable();
            
            // Counseling provided
            $table->text('counseling_topics')->nullable();
            $table->boolean('infant_care_advice_given')->default(false);
            $table->boolean('feeding_guidance_given')->default(false);
            $table->boolean('cord_care_advice_given')->default(false);
            $table->boolean('hygiene_safety_advice_given')->default(false);
            $table->boolean('danger_signs_explained')->default(false);
            
            // Assessment and plan
            $table->text('clinical_summary')->nullable();
            $table->enum('health_status', ['normal', 'at_risk', 'needs_referral', 'referred'])->default('normal');
            $table->text('referral_reason')->nullable();
            $table->text('referral_destination')->nullable();
            $table->text('management_plan')->nullable();
            
            // Follow-up scheduling
            $table->dateTime('next_follow_up_date')->nullable();
            $table->text('next_follow_up_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_follow_ups');
    }
};
