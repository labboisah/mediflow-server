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
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id')->nullable();
            $table->foreignId('recorded_by');
            $table->time('time');
            $table->timestamp('date');
            $table->string('blood_pressure')->nullable();
            $table->string('mate_pulse')->nullable();
            $table->string('temperature')->nullable();
            $table->string('respiration')->nullable();
            $table->string('drop_rate')->nullable();
            $table->string('constraction')->nullable();
            $table->string('fits')->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });
    }

            

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
