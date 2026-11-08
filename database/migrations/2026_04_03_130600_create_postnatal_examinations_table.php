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
        Schema::create('postnatal_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // mother
            $table->foreignId('recorded_by')->constrained('users');
            
            // Examination timing
            $table->dateTime('examination_date_time');
            $table->integer('hours_post_delivery')->nullable();
            $table->enum('examination_time', ['immediate_0-2h', '6-12h', '24h', '48h', 'day4_6', 'week1', 'week2', 'week6'])->nullable();
            
            // Vital signs
            $table->string('blood_pressure')->nullable();
            $table->string('pulse_rate')->nullable();
            $table->string('temperature')->nullable();
            $table->string('respiration_rate')->nullable();
            
            // General condition
            $table->text('general_appearance')->nullable();
            $table->string('consciousness_level')->nullable();
            $table->text('skin_colour')->nullable();
            
            // Uterine assessment
            $table->string('uterine_size')->nullable(); // in terms of weeks (e.g., 20 week size)
            $table->string('uterine_consistency')->nullable(); // firm, soft, boggy
            $table->string('uterine_tenderness')->nullable();
            $table->string('fundal_height')->nullable();
            
            // Lochia assessment
            $table->string('lochia_type')->nullable(); // rubra, serosa, alba
            $table->string('lochia_amount')->nullable(); // minimal, moderate, heavy
            $table->string('lochia_odour')->nullable();
            $table->string('clot_presence')->nullable();
            
            // Per-vaginal examination
            $table->text('perineal_assessment')->nullable();
            $table->string('perineal_wound_status')->nullable(); // intact, sutured, healing, healed
            $table->text('perineal_pain')->nullable();
            $table->text('vaginal_examination')->nullable();
            
            // Breast assessment
            $table->text('breast_examination')->nullable();
            $table->text('nipple_condition')->nullable();
            $table->text('breast_engorgement')->nullable();
            $table->text('breast_milk_expression')->nullable();
            $table->boolean('breastfeeding_successful')->default(false);
            $table->text('breastfeeding_problems')->nullable();
            
            // Abdominal examination
            $table->text('abdominal_examination')->nullable();
            $table->text('wound_assessment')->nullable(); // if caesarean section
            $table->text('drain_status')->nullable(); // if applicable
            
            // Lower limbs assessment
            $table->text('lower_limbs_examination')->nullable();
            $table->text('oedema_assessment')->nullable();
            $table->text('calf_tenderness')->nullable();
            $table->text('signs_of_dvt')->nullable();
            
            // Psychological status
            $table->text('maternal_mood')->nullable();
            $table->text('emotional_state')->nullable();
            $table->boolean('signs_of_depression')->default(false);
            $table->text('bonding_with_baby')->nullable();
            
            // Complications assessment
            $table->text('complications_identified')->nullable();
            $table->text('infection_signs')->nullable();
            $table->text('bleeding_assessment')->nullable();
            $table->text('hypertension_assessment')->nullable();
            
            // Functional status
            $table->text('sleep_patterns')->nullable();
            $table->text('pain_level')->nullable();
            $table->text('activity_tolerance')->nullable();
            $table->text('perineal_care_ability')->nullable();
            
            // Counseling and education
            $table->text('counseling_topics')->nullable();
            $table->boolean('contraception_discussed')->default(false);
            $table->text('contraception_method_chosen')->nullable();
            $table->boolean('hygiene_taught')->default(false);
            $table->boolean('danger_signs_explained')->default(false);
            
            // Overall assessment and plan
            $table->text('clinical_summary')->nullable();
            $table->enum('recovery_status', ['normal', 'complicated', 'needs_referral'])->default('normal');
            $table->text('management_plan')->nullable();
            $table->text('medications_prescribed')->nullable();
            
            // Follow-up plan
            $table->text('follow_up_plan')->nullable();
            $table->dateTime('next_follow_up_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postnatal_examinations');
    }
};
