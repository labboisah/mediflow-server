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
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id');
            $table->decimal('body_temperature', 5, 2);
            $table->integer('blood_pressure_systolic');
            $table->integer('blood_pressure_diastolic');
            $table->integer('heart_rate');
            $table->integer('respiratory_rate');
            $table->decimal('oxygen_saturation', 5, 2);
            $table->decimal('blood_glucose', 8, 2)->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by');
            $table->dateTime('recorded_date');
            $table->softDeletes();
            $table->timestamps();

            $table->index('patient_visit_id');
            $table->index('recorded_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
