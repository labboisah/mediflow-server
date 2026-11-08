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
        Schema::create('antenatal_cares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->foreignId('patient_visit_id');
            $table->foreignId('recorded_by');
            
            // Pregnancy details
            $table->date('last_menstrual_period')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->integer('gestational_weeks')->nullable();
            $table->integer('number_of_fetuses')->default(1);
            $table->string('pregnancy_type')->nullable(); // singleton, twins, etc.
            
            // Vital signs
            $table->string('blood_pressure')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            
            // Examination findings
            $table->text('abdominal_examination')->nullable();
            $table->string('fundal_height')->nullable();
            $table->string('fetal_heart_rate')->nullable();
            $table->text('fetal_movement')->nullable();
            $table->text('vaginal_examination')->nullable();
            
            // Investigations
            $table->text('urine_analysis')->nullable();
            $table->text('blood_tests')->nullable();
            $table->text('ultrasound_findings')->nullable();
            
            // Risk factors and complications
            $table->text('risk_factors')->nullable();
            $table->text('complications')->nullable();
            $table->text('management_plan')->nullable();
            
            // Counseling and education
            $table->text('counseling_topics')->nullable();
            $table->boolean('took_supplements')->default(true);
            
            // Status and notes
            $table->text('clinical_notes')->nullable();
            $table->enum('status', ['normal', 'complicated', 'high_risk'])->default('normal');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antenatal_cares');
    }
};
