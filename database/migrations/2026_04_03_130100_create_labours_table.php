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
        Schema::create('labours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('users');
            
            // Labour onset
            $table->dateTime('labour_onset_time');
            $table->string('mode_of_onset')->nullable(); // spontaneous, induced
            $table->text('reason_for_induction')->nullable();
            
            // Labour details
            $table->integer('gestational_weeks')->nullable();
            $table->string('labour_type')->nullable(); // primigravida, multigravida, etc.
            $table->text('previous_obstetric_history')->nullable();
            
            // Pre-labour assessment
            $table->string('cervical_state')->nullable();
            $table->string('show')->nullable(); // bloody show present/absent
            $table->string('rupture_of_membranes')->nullable(); // intact, spontaneous rupture, artificial rupture
            $table->text('liquor')->nullable(); // characteristics of amniotic fluid
            
            // Maternal vitals at admission
            $table->string('blood_pressure')->nullable();
            $table->string('pulse_rate')->nullable();
            $table->string('temperature')->nullable();
            $table->string('respiration_rate')->nullable();
            
            // Labour progress
            $table->enum('stage', ['not_started', 'first_stage', 'second_stage', 'third_stage', 'completed'])->default('not_started');
            $table->dateTime('first_stage_started_at')->nullable();
            $table->dateTime('second_stage_started_at')->nullable();
            $table->dateTime('third_stage_started_at')->nullable();
            
            // Fetal heart rate monitoring
            $table->string('fetal_heart_rate')->nullable();
            $table->text('fetal_monitoring_notes')->nullable();
            
            // Complications during labour
            $table->text('complications')->nullable();
            
            // General notes
            $table->text('clinical_notes')->nullable();
            $table->enum('status', ['ongoing', 'completed', 'complicated'])->default('ongoing');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labours');
    }
};
