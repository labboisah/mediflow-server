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
        Schema::create('labour_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id')->constrained('labours')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('users');
            
            // Recording time
            $table->dateTime('recorded_at');
            
            // Contraction assessment
            $table->string('contraction_frequency')->nullable(); // e.g., "3 in 10 minutes"
            $table->string('contraction_duration')->nullable(); // e.g., "40 seconds"
            $table->string('contraction_intensity')->nullable(); // mild, moderate, strong
            
            // Cervical findings
            $table->integer('cervical_dilation')->nullable(); // 0-10 cm
            $table->integer('cervical_effacement')->nullable(); // 0-100%
            $table->string('cervical_consistency')->nullable(); // firm, medium, soft
            $table->string('cervical_position')->nullable(); // posterior, mid, anterior
            
            // Descent (station -5 to +5)
            $table->integer('fetal_station')->nullable();
            $table->string('fetal_position')->nullable(); // OA, OP, LOA, LOP, ROA, ROP, etc.
            
            // Maternal assessment
            $table->string('uterine_tone')->nullable();
            $table->string('uterine_tenderness')->nullable();
            $table->text('vaginal_examination_findings')->nullable();
            
            // Fetal assessment
            $table->string('fetal_heart_rate')->nullable();
            $table->text('fetal_heart_variability')->nullable();
            $table->text('fetal_movements')->nullable();
            $table->text('meconium_stained_liquor')->nullable();
            
            // Maternal vital signs
            $table->string('blood_pressure')->nullable();
            $table->string('pulse_rate')->nullable();
            $table->string('temperature')->nullable();
            
            // Tolerance of labour
            $table->text('maternal_pain_relief')->nullable();
            $table->text('coping_mechanisms')->nullable();
            
            // Management/interventions during this period
            $table->text('interventions')->nullable();
            $table->text('medications_given')->nullable();
            $table->text('observations_and_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labour_progresses');
    }
};
