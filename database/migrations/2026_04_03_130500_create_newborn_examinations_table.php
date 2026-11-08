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
        Schema::create('newborn_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newborn_id')->constrained('newborns')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('users');
            
            // Examination details
            $table->dateTime('examination_date_time');
            $table->integer('hours_after_birth')->nullable();
            
            // Vital signs
            $table->string('temperature')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('respiratory_rate')->nullable();
            
            // Anthropometry
            $table->string('weight')->nullable();
            $table->string('length')->nullable();
            $table->string('head_circumference')->nullable();
            $table->string('chest_circumference')->nullable();
            
            // General examination
            $table->text('general_appearance')->nullable();
            $table->text('skin_examination')->nullable();
            $table->text('head_and_neck')->nullable();
            $table->text('eyes_examination')->nullable();
            $table->text('ear_examination')->nullable();
            $table->text('mouth_and_throat')->nullable();
            
            // Cardiovascular examination
            $table->text('heart_sounds')->nullable();
            $table->text('pulses')->nullable();
            $table->text('capillary_refill')->nullable();
            
            // Respiratory examination
            $table->text('chest_expansion')->nullable();
            $table->text('breath_sounds')->nullable();
            $table->text('nasal_breathing')->nullable();
            
            // Abdominal examination
            $table->text('abdomen_shape')->nullable();
            $table->text('umbilical_cord_check')->nullable();
            $table->text('hepatomegaly_splenomegaly')->nullable();
            $table->text('bowel_sounds')->nullable();
            
            // Genitourinary examination
            $table->text('genitalia_examination')->nullable();
            $table->text('urinary_output')->nullable();
            $table->text('stool_output')->nullable();
            
            // Neurological examination
            $table->text('reflex_assessment')->nullable();
            $table->text('muscle_tone')->nullable();
            $table->text('developmental_screening')->nullable();
            
            // Musculoskeletal examination
            $table->text('extremities_examination')->nullable();
            $table->text('hip_examination')->nullable();
            $table->text('spine_examination')->nullable();
            
            // Special findings
            $table->text('abnormal_findings')->nullable();
            $table->text('congenital_anomalies')->nullable();
            
            // Jaundice assessment
            $table->string('jaundice_present')->nullable();
            $table->text('jaundice_level')->nullable();
            $table->text('jaundice_management')->nullable();
            
            // Feeding assessment
            $table->string('feeding_type')->nullable(); // breast, bottle, mixed
            $table->text('feeding_tolerance')->nullable();
            $table->text('feeding_challenges')->nullable();
            
            // Overall assessment
            $table->text('clinical_summary')->nullable();
            $table->enum('exam_status', ['normal', 'abnormal', 'needs_follow_up', 'referral_needed'])->default('normal');
            
            // Recommendation for next follow-up
            $table->text('follow_up_plans')->nullable();
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
        Schema::dropIfExists('newborn_examinations');
    }
};
